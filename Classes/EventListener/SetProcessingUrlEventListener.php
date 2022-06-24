<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/typo3-image-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Typo3ImageProxy\EventListener;

use StefanFroemken\Typo3ImageProxy\Service\ImgProxyService;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Set processing URL of processed image
 */
class SetProcessingUrlEventListener
{
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
        $arr = GeneralUtility::getIndpEnv('_ARRAY');
        $publicUrl = $this->imgProxyService->getProcessingUrl(
            $event->getFile(),
            $event->getProcessedFile(),
            $event->getConfiguration()
        );

        // If publicUrl is empty we already create a log entry in ImgProxyService
        if ($publicUrl) {
            $event->getProcessedFile()->updateProcessingUrl($publicUrl);
        }
    }
}
