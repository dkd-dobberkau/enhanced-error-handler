<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Enhanced Error Handler',
    'description' => 'Enhanced TYPO3 Debug Exception Handler with copy-to-clipboard functionality similar to Laravel Ignition',
    'category' => 'misc',
    'author' => 'dkd Internet Service GmbH',
    'author_email' => 'info@dkd.de',
    'author_company' => 'dkd Internet Service GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
