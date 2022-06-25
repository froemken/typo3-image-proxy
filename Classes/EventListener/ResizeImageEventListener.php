<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/typo3-image-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Typo3ImageProxy\EventListener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use StefanFroemken\Typo3ImageProxy\Service\ImgProxyService;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resize image
 */
class ResizeImageEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ImgProxyService
     */
    protected $imgProxyService;

    public function __construct(ImgProxyService $imgProxyService)
    {
        $this->imgProxyService = $imgProxyService;
    }

    public function __invoke(BeforeFileProcessingEvent $event): void
    {
        $processingUrl = $this->imgProxyService->getProcessingUrl(
            $event->getFile(),
            $event->getProcessedFile(),
            $event->getConfiguration()
        );

        $task = $event->getProcessedFile()->getTask();
        $temporaryFilePath = $this->getTemporaryFilePath($task);
        file_put_contents($temporaryFilePath, $processingUrl);

        $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($temporaryFilePath);
        if ($imageDimensions === false) {
            $this->logger->error('File "' . $event->getFile()->getName() . '" could not be resized by ImgProxy. Maybe you are on localhost.');
            return;
        }

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
