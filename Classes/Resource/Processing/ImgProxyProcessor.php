<?php

/*
 * This file is part of the package stefanfroemken/typo3-image-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Typo3ImageProxy\Resource\Processing;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        return in_array($task->getTargetFileExtension(), $this->allowedImageExtensions, true);
    }

    /**
     * Processes the given task and sets the processing result in the task object.
     *
     * @param TaskInterface $task
     */
    public function processTask(TaskInterface $task)
    {
        $keyBin = pack("H*" , $this->imgProxyKey);
        if (empty($keyBin)) {
            \TYPO3\CMS\Core\Utility\DebugUtility::debug('Key expected to be hex-encoded string');
        }

        $saltBin = pack("H*" , $this->imgProxySalt);
        if (empty($saltBin)) {
            \TYPO3\CMS\Core\Utility\DebugUtility::debug('Salt expected to be hex-encoded string');
        }

        $publicUrlOfImage = $this->getPublicUrlOfImage($task->getSourceFile());

        // PATH: /{$resize}/{$width}/{$height}/{$gravity}/{$enlarge}/{$encodedUrl}.{$extension}"
        $path = sprintf(
            '/%s/%d/%d/%s/%d/%s.%s',
            'fit',
            $this->imageMaxWidth,
            $this->imageMaxHeight,
            'no',
            0,
            rtrim(strtr(base64_encode($publicUrlOfImage), '+/', '-_'), '='),
            $task->getTargetFileExtension()
        );

        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $saltBin . $path, $keyBin, true)), '+/', '-_'), '=');

        /*$task->getTargetFile()->setName($task->getTargetFileName());
        $task->getTargetFile()->updateProperties(
            ['width' => $imageDimensions[0], 'height' => $imageDimensions[1], 'size' => filesize($result['filePath']), 'checksum' => $task->getConfigurationChecksum()]
        );
        $task->getTargetFile()->setContents(
            GeneralUtility::getUrl(
                sprintf(
                    '%s%s%s',
                    $this->imgProxyUrl,
                    $signature,
                    $path
                )
            )
        );*/

        \TYPO3\CMS\Core\Utility\DebugUtility::debug(
            sprintf(
                '%s%s%s',
                $this->imgProxyUrl,
                $signature,
                $path
            ),
            'Signed ImgProxy Path'
        );
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
            GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
            $pathPart,
            $filePart
        );
    }
}
