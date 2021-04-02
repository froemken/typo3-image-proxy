<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 ImgProxy',
    'description' => 'Resize uploaded images with external imgproxy service.',
    'category' => 'backend',
    'state' => 'alpha',
    'clearCacheOnLoad' => 0,
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
