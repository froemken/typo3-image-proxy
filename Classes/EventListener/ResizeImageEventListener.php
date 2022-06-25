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
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;

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
        $this->imgProxyService->resizeImage($event->getFile(), $event->getProcessedFile());
    }
}
