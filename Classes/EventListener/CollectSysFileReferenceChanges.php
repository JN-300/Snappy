<?php

declare(strict_types=1);

namespace JeNe\Snappy\EventListener;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class CollectSysFileReferenceChanges
{

    const TABLE = 'sys_file_reference';
    public function __invoke(\JeNe\Snappy\Event\SnapshotAfterLoadingPageDataEvent $event): void
    {
        $snapshotStorageObject = $event->getSnapshotStoreObject();
        $pid = $snapshotStorageObject->pageData['uid'];
        $sysFileReferenceChanges = $this->collectSysFileReferenceChanges($pid);
        $event->getSnapshotStoreObject()->addElementData(self::TABLE, $sysFileReferenceChanges);
    }

    public function collectSysFileReferenceChanges(int $pid)
    {
        $sysFileReferenceQuery = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->createQueryBuilder();
        $sysFileReferenceQuery->getRestrictions()->removeAll();

        $files =  $sysFileReferenceQuery
            ->select('*')
            ->from(self::TABLE)
            ->where($sysFileReferenceQuery->expr()->in('pid', $pid))
            ->executeQuery()
            ->fetchAllAssociative();

        return $files;
    }
}
