<?php

declare(strict_types=1);

namespace JeNe\Snappy\EventListener;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class CollectContentForPage
{
    const TABLE = 'tt_content';

    private $ignoreFields = [
        'tstamp',
        'crdate',
        't3ver_oid',
        't3ver_wsid',
        't3ver_state',
        't3ver_stage',
    ];


    public function __construct()
    {
    }

    public function __invoke(\JeNe\Snappy\Event\SnapshotAfterLoadingPageDataEvent $event): void
    {
        $pageId = $event->getSnapshotStoreObject()->pageData['uid'];

        $data = $this->loadData($pageId);
        $data = $this->filterData($data);

        $event->getSnapshotStoreObject()->addElementData(self::TABLE, $data);
    }

    private function filterData(array $data):array
    {
        return
            array_map(fn($dataset)
                => array_filter(
                    $dataset,
                    fn($fieldKey) => !in_array($fieldKey, $this->ignoreFields), ARRAY_FILTER_USE_KEY),
                $data
            );
    }
    private function loadData(int $pageId):array
    {
        $ttContentQuery = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->createQueryBuilder();
        $ttContentQuery->getRestrictions()->removeAll();

        return $ttContentQuery
            ->select('*')
            ->from(self::TABLE)
            ->where($ttContentQuery->expr()->eq('pid', $pageId))
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
