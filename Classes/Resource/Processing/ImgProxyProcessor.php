<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/typo3-image-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Typo3ImageProxy\Resource\Processing;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Resize uploaded images
 */
class ImgProxyProcessor implements ProcessorInterface
{
    /**
     * @var string
     */
    protected $imgProxyUrl = 'http://example.com:8080/';

    /**
     * @var string
     */
    protected $imgProxyKey = '';

    /**
     * @var string
     */
    protected $imgProxySalt = '';

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

    public function __construct()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        $imgProxyUrl = (string)rtrim(
            $extensionConfiguration->get('typo3_image_proxy', 'imgProxyUrl'),
            '/'
        ) . '/';
        if (GeneralUtility::isValidUrl($imgProxyUrl)) {
            $this->imgProxyUrl = $imgProxyUrl;
        }

        $this->imgProxyKey = (string)$extensionConfiguration->get('typo3_image_proxy', 'imgProxyKey');
        $this->imgProxySalt = (string)$extensionConfiguration->get('typo3_image_proxy', 'imgProxySalt');
        $this->altLocalHostName = (string)$extensionConfiguration->get('typo3_image_proxy', 'altLocalHostName');
        $this->imageMaxWidth = (int)$extensionConfiguration->get('typo3_image_proxy', 'maxImageWidth');
        $this->imageMaxHeight = (int)$extensionConfiguration->get('typo3_image_proxy', 'maxImageHeight');
    }

    /**
     * Returns TRUE if this processor can process the given task.
     *
     * @param TaskInterface $task
     * @return bool
     */
    public function canProcessTask(TaskInterface $task)
    {
        $sourceFile = $task->getSourceFile();
        $this->configuration = $this->preProcessConfiguration($task->getConfiguration());

        // Do not scale image if the source file has a size and the target size is larger
        if (
            $sourceFile->getProperty('width') > 0 && $sourceFile->getProperty('height') > 0
            && $this->configuration['width'] > $sourceFile->getProperty('width')
            && $this->configuration['height'] > $sourceFile->getProperty('height')
        ) {
            return false;
        }

        return in_array($task->getTargetFileExtension(), $this->allowedImageExtensions, true);
    }

    /**
     * Processes the given task and sets the processing result in the task object.
     *
     * @param TaskInterface $task
     */
    public function processTask(TaskInterface $task)
    {
        // early return, if binary representation of key/salt are invalid
        $keyBin = pack("H*" , $this->imgProxyKey);
        $saltBin = pack("H*" , $this->imgProxySalt);
        if (empty($keyBin) || empty($saltBin)) {
            \TYPO3\CMS\Core\Utility\DebugUtility::debug('Key or Salt expected to be hex-encoded string');
            $task->setExecuted(false);
            return;
        }

        $publicUrlOfImage = $this->getPublicUrlOfImage($task->getSourceFile());

        // PATH: /{$resize}/{$width}/{$height}/{$gravity}/{$enlarge}/{$encodedUrl}.{$extension}"
        $path = sprintf(
            '/%s/%d/%d/%s/%d/%s.%s',
            'fit',
            $this->configuration['width'],
            $this->imageMaxHeight['height'],
            'no',
            0,
            rtrim(strtr(base64_encode($publicUrlOfImage), '+/', '-_'), '='),
            $task->getTargetFileExtension()
        );

        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $saltBin . $path, $keyBin, true)), '+/', '-_'), '=');

        $temporaryFilePath = $this->getTemporaryFilePath($task);
        file_put_contents(
            $temporaryFilePath,
            GeneralUtility::getUrl(
                sprintf(
                    '%s%s%s',
                    $this->imgProxyUrl,
                    $signature,
                    $path
                )
            )
        );

        $task->setExecuted(true);
        $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($temporaryFilePath);
        $task->getTargetFile()->setName($task->getTargetFileName());
        $task->getTargetFile()->updateProperties(
            [
                'width' => $imageDimensions[0],
                'height' => $imageDimensions[1],
                'size' => filesize($temporaryFilePath),
                'checksum' => $task->getConfigurationChecksum()
            ]
        );
        $task->getTargetFile()->updateWithLocalFile($temporaryFilePath);
    }

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

    /**
     * Enforce default configuration for preview processing
     *
     * @param array $configuration
     * @return array
     */
    public function preProcessConfiguration(array $configuration): array
    {
        $configuration = array_replace($this->defaultConfiguration, $configuration);
        $configuration['width'] = MathUtility::forceIntegerInRange($configuration['width'], 1, $this->imageMaxWidth);
        $configuration['height'] = MathUtility::forceIntegerInRange($configuration['height'], 1, $this->imageMaxHeight);

        return array_filter(
            $configuration,
            function ($value, $name) {
                return !empty($value) && in_array($name, ['width', 'height'], true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Returns the path to a temporary file for processing
     *
     * @param TaskInterface $task
     * @return string
     */
    protected function getTemporaryFilePath(TaskInterface $task): string
    {
        return GeneralUtility::tempnam(
            $task->getName() . '_by_imgproxy', '.' . $task->getTargetFileExtension()
        );
    }

    protected function getGraphicalFunctionsObject(): GraphicalFunctions
    {
        return GeneralUtility::makeInstance(GraphicalFunctions::class);
    }
}
