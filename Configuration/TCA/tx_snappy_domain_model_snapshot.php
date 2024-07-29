<?php

defined('TYPO3') or die();

return [
    'ctrl' => [

        'hideTable' => true,
        'title' => 'snapshots',
        'label' => 'title',

        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
//        'delete' => 'deleted',

        'searchFields' => 'title',

        'enablecolumns' => [
        ],

        'versioningWS' => false
    ],
    'columns' => [
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'snapshot' => [
            'label' => 'Snapshot data',
            'config' => [
                'type' => 'input'
            ],
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => 'title',
        ],
        'visibility' => [
            'showitem' => 'hidden',
        ],
    ],
    'types' => [
        0 => [
            'showitem' => 'title',
        ],
    ],
];


