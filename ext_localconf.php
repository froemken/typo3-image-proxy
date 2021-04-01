<?php

defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['ImgProxyProcessor'] = [
    'className' => \StefanFroemken\Typo3ImageProxy\Resource\Processing\ImgProxyProcessor::class,
    'before' => [
        'LocalImageProcessor',
        'DeferredBackendImageProcessor'
    ]
];
