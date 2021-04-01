<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 ImgProxy',
    'description' => 'Resize uploaded images with imgproxy.',
    'category' => 'backend',
    'state' => 'alpha',
    'clearCacheOnLoad' => 0,
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
