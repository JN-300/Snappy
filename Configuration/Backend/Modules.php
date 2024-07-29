<?php

use JeNe\Snappy\Backend\Controller\SnapshotController;
/** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactorey */
$iconFactorey = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
return [
    'web_snappy' => [
        'parent' => 'web',
        'position' => [
            'after' => 'web_info'
        ],
        'access' => 'user',
        'workspace' => 'live',
        'path' => 'module/web/snappy',
        'labels' => 'LLL:EXT:snappy/Resources/Private/Language/snappy.xlf',

        'extensionName' => 'Snappy',
       'iconIdentifier' => 'module-workspaces',
        'controllerActions' => [
            SnapshotController::class => [
                'index',
                'create',
                'store',
                'delete',
                'restore',
            ]
        ]
    ]
];
