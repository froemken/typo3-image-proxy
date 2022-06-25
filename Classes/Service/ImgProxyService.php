<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/typo3-image-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Typo3ImageProxy\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Service to build processing URL for imgproxy
 */
class ImgProxyService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var string
     */
    protected $imgProxyUrl = 'http://example.com:8080/';

    /**
     * @var string
     */
    protected $imgProxyKeyBin = '';

    /**
     * @var string
     */
    protected $imgProxySaltBin = '';

    /**
     * @var string
     */
    protected $altLocalHostName = '';

    /**
     * @var int
     */
    protected $imageMaxWidth = 1024;

    /**
     * @var int
     */
    protected $imageMaxHeight = 1024;

    /**
     * @var string[]
     */
    protected $allowedImageExtensions = [
        'jpg',
        'jpeg',
        'png'
    ];

    /**
     * @var array
     */
    protected $defaultConfiguration = [
        'width' => 64,
        'height' => 64,
    ];

    /**
     * @var array
     */
    protected $configuration = [];

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        RequestFactory $requestFactory
    ) {
        $this->requestFactory = $requestFactory;

        $this->imgProxyUrl = $this->getImgProxyUrl($extensionConfiguration);
        $this->altLocalHostName = (string)$extensionConfiguration->get('typo3_image_proxy', 'altLocalHostName');
        $this->imageMaxWidth = (int)$extensionConfiguration->get('typo3_image_proxy', 'maxImageWidth');
        $this->imageMaxHeight = (int)$extensionConfiguration->get('typo3_image_proxy', 'maxImageHeight');

        $this->imgProxyKeyBin = pack(
            'H*',
            (string)$extensionConfiguration->get('typo3_image_proxy', 'imgProxyKey')
        );
        $this->imgProxySaltBin = pack(
            'H*',
            (string)$extensionConfiguration->get('typo3_image_proxy', 'imgProxySalt')
        );

        // Return value to empty string, if binary representation of key/salt are invalid
        if (empty($this->imgProxyKeyBin) || empty($this->imgProxySaltBin)) {
            $this->imgProxyKeyBin = '';
            $this->imgProxySaltBin = '';
            $this->logger->error('ImgProxy key and/or salt expected to be hex-encoded string');
        }
    }

    /**
     * Get processing URL for external ImgProxy service.
     * This method also sets the new dimensions to $targetFile
     */
    public function getProcessingUrl(
        FileInterface $sourceFile,
        FileInterface $targetFile,
        array $configuration
    ): string {
        if ($this->imgProxyKeyBin === '' || $this->imgProxySaltBin === '') {
            return '';
        }

        if (!$this->isValidFile($sourceFile)) {
            return '';
        }

        $publicUrlOfImage = $this->getPublicUrlOfImage($sourceFile);
        $width = $this->mergeWithDefaultConfiguration($configuration)['width'];
        $height = $this->mergeWithDefaultConfiguration($configuration)['height'];
        $targetFile->updateProperties([
            'width' => $width,
            'height' => $height,
        ]);

        // PATH: /{$resize}/{$width}/{$height}/{$gravity}/{$enlarge}/{$encodedUrl}.{$extension}"
        $path = sprintf(
            '/%s/%d/%d/%s/%d/%s.%s',
            'fit',
            $width,
            $height,
            'no',
            0,
            rtrim(strtr(base64_encode($publicUrlOfImage), '+/', '-_'), '='),
            $targetFile->getExtension()
        );

        $signature = hash_hmac('sha256', $this->imgProxySaltBin . $path, $this->imgProxyKeyBin, true);
        $signature = base64_encode($signature);
        $signature = strtr($signature, '+/', '-_');
        $signature = rtrim($signature, '=');

        return sprintf(
            '%s%s%s',
            $this->imgProxyUrl,
            $signature,
            $path
        );
    }

    public function resizeImage(FileInterface $sourceFile): void
    {
        // We want to resize the original file
        if (!$sourceFile instanceof File) {
            return;
        }

        // Do not resize, if resize was already done
        if (
            $sourceFile->getProperty('width') < $this->imageMaxWidth
            || $sourceFile->getProperty('height') < $this->imageMaxHeight
        ) {
            return;
        }

        $processingUrl = $this->getProcessingUrl(
            $sourceFile,
            $sourceFile,
            [
                'width' => $sourceFile->getProperty('width'),
                'height' => $sourceFile->getProperty('height')
            ]
        );

        $temporaryFilePath = $this->getTemporaryFilePath($sourceFile);
        file_put_contents(
            $temporaryFilePath,
            $this->requestFactory->request($processingUrl)->getBody()->getContents()
        );

        $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($temporaryFilePath);
        if ($imageDimensions === false) {
            $this->logger->error('File "' . $sourceFile->getName() . '" could not be resized by ImgProxy. Maybe you are on localhost.');
            return;
        }

        $sourceFile->updateProperties(
            [
                'width' => $imageDimensions[0],
                'height' => $imageDimensions[1],
                'size' => filesize($temporaryFilePath),
                'checksum' => $sourceFile->calculateChecksum()
            ]
        );

        try {
            $sourceFile->getStorage()->replaceFile($sourceFile, $temporaryFilePath);
        } catch (\Exception $exception) {
            $this->logger->error('ImgProxy: File "' . $sourceFile->getName() . '" could not be renamed. Error: ' . $exception->getMessage());
        }
    }

    /**
     * Returns the host:port combination for ImgProxy service
     */
    protected function getImgProxyUrl(ExtensionConfiguration $extensionConfiguration): string
    {
        try {
            $imgProxyUrl = (string)$extensionConfiguration->get('typo3_image_proxy', 'imgProxyUrl');
            $imgProxyUrl = rtrim($imgProxyUrl, '/') . '/';

            if (!GeneralUtility::isValidUrl($imgProxyUrl)) {
                $imgProxyUrl = '';
            }
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $extensionConfigurationExtensionNotConfiguredException) {
            $this->logger->error('Extension configuration "imgProxyUrl" for EXT:typo3_image_proxy not found. Please configure extension in settings module.');
            $imgProxyUrl = '';
        }

        return $imgProxyUrl;
    }

    protected function isValidFile(FileInterface $file): bool
    {
        $isValid = in_array($file->getExtension(), $this->allowedImageExtensions, true);
        if (!$isValid) {
            $this->logger->error('Given file "' . $file->getName() . '" is not valid and can not be processed by ImgProxy service.');
        }

        return $isValid;
    }

    /**
     * Returns the public URL of a local image.
     * This path will be sent to ImgProxy service, so, that the service can download the file for processing
     */
    protected function getPublicUrlOfImage(FileInterface $sourceFile): string
    {
        $absolutePathToContainingFolder = PathUtility::dirname(
            Environment::getPublicPath() . '/' . $sourceFile->getPublicUrl()
        );
        $pathPart = PathUtility::getRelativePath(
            Environment::getPublicPath(),
            $absolutePathToContainingFolder
        );
        $filePart = substr(
            Environment::getPublicPath() . '/' . $sourceFile->getPublicUrl(),
            strlen($absolutePathToContainingFolder) + 1
        );

        return sprintf(
            '%s/%s%s',
            $this->altLocalHostName ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
            $pathPart,
            $filePart
        );
    }

    protected function getTemporaryFilePath(FileInterface $file): string
    {
        return GeneralUtility::tempnam(
            'ImgProxy_',
            '.' . $file->getExtension()
        );
    }

    protected function getGraphicalFunctionsObject(): GraphicalFunctions
    {
        return GeneralUtility::makeInstance(GraphicalFunctions::class);
    }

    protected function mergeWithDefaultConfiguration(array $configuration): array
    {
        $configuration = array_replace($this->defaultConfiguration, $configuration);
        $configuration['width'] = MathUtility::forceIntegerInRange($configuration['width'], 1, $this->imageMaxWidth);
        $configuration['height'] = MathUtility::forceIntegerInRange($configuration['height'], 1, $this->imageMaxHeight);

        return array_filter(
            $configuration,
            static function ($value, $name) {
                return !empty($value) && in_array($name, ['width', 'height'], true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
}
