<?php

defined('TYPO3_MODE') or die();

call_user_func(static function () {
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['StefanFroemken']['Typo3ImageProxy']['writerConfiguration'])) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['StefanFroemken']['Typo3ImageProxy']['writerConfiguration'] = [
            \Psr\Log\LogLevel::ERROR => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                    'logFileInfix' => 'imgproxy',
                ],
            ],
        ];
    }
});
