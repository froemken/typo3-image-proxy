<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/typo3-image-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Typo3ImageProxy\Resource\Processing;

use StefanFroemken\Typo3ImageProxy\Service\ImgProxyService;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resize uploaded images
 */
class ImgProxyProcessor implements ProcessorInterface
{
    /**
     * @var ImgProxyService
     */
    protected $imgProxyService;

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

    public function __construct(ImgProxyService $imgProxyService)
    {
        $this->imgProxyService = $imgProxyService;
    }

    public function canProcessTask(TaskInterface $task): bool
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
        $processingUrl = $this->imgProxyService->getProcessingUrl(
            $task->getSourceFile(),
            $task->getTargetFile(),
            $task->getConfiguration()
        );

        $temporaryFilePath = $this->getTemporaryFilePath($task);
        file_put_contents($temporaryFilePath, $processingUrl);

        $task->setExecuted(true);
        $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($temporaryFilePath);
        $task->getTargetFile()->setName('ByImgProxy_' . $task->getTargetFileName());
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

    /**
     * Returns the path to a temporary file for processing
     *
     * @param TaskInterface $task
     * @return string
     */
    protected function getTemporaryFilePath(TaskInterface $task): string
    {
        return GeneralUtility::tempnam(
            $task->getName(),
            '.' . $task->getTargetFileExtension()
        );
    }

    protected function getGraphicalFunctionsObject(): GraphicalFunctions
    {
        return GeneralUtility::makeInstance(GraphicalFunctions::class);
    }
}
