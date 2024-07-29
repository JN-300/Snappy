<?php

declare(strict_types=1);

namespace JeNe\Snappy\EventListener;

use JeNe\Snappy\Event\SnapshotAfterRestoringPageDataEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RestoreTtContent
{
    const TABLE = 'tt_content';

    private $ignoreFields = [
        'tstamp',
        'crdate'
    ];

    private int $currentPid;
    private SnapshotAfterRestoringPageDataEvent $event;
    public function __construct(
        private readonly DataHandler $dataHandler
    )
    {}

    public function __invoke(\JeNe\Snappy\Event\SnapshotAfterRestoringPageDataEvent $event): void
    {
        $this->currentPid = $event->getSnapshotStoreObject()->pageData['uid'];

        $ttContentData = $event->getSnapshotStoreObject()->getElementData(self::TABLE);
        $ttContentData = $this->prepareData($ttContentData);

        $this->updateDeleteStatusForElements($ttContentData);

        // need to group by language, 'cause loclizsed items can only update after the orig item
        foreach ($this->groupByLanguage($ttContentData) as $lang => $items ) {
            $this->processSorting($items);
            $this->processData($items);
        }

    }

    private function prepareData(?array $data): ?array
    {
        if ($data) {
            usort($data, function ($a, $b) {
                return $a['sorting'] <=> $b['sorting'];
            });
            $data = array_map(fn($dataset) => array_filter($dataset, fn($fieldKey) => !in_array($fieldKey, $this->ignoreFields), ARRAY_FILTER_USE_KEY), $data);
        }

        return $data;
    }
    private function groupByLanguage(?array $data): ?array
    {
        $groupedData = [];
        $data = array_filter($data, fn($item) => $item['deleted'] === 0);
        foreach ($data as $dataset) {
            $groupedData[$dataset['sys_language_uid']][] = $dataset;
        }
        ksort($groupedData);

        return $groupedData;
    }

    private function processData(?array $updateData):void
    {
        $data = [];
        foreach ($updateData as $dataSet) {
            $data[self::TABLE][$dataSet['uid']] = $dataSet;
        }
        $this->dataHandler->start($data, []);
        $this->dataHandler->process_datamap();
    }

    private function processSorting(?array $updateData):void
    {
        $updateData = array_reverse($updateData);
        foreach ($updateData as $dataSet) {
            $cmd[self::TABLE][$dataSet['uid']]['move'] = $dataSet['pid'];
        }
        $this->dataHandler->start([], $cmd);
        $this->dataHandler->process_cmdmap();
    }

    private function updateDeleteStatusForElements(?array $updateData):void
    {
        $keepItems = array_filter($updateData, fn($item) => $item['deleted'] === 0);
        $keepItems = array_map(fn($item) => ['uid' => $item['uid'], 'pid' => $item['pid']], $keepItems);
        $keepItemIds = array_column($keepItems, 'uid');


        $query = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->createQueryBuilder();
        $query->getRestrictions()->removeAll();

        $query->select('uid')
            ->from(self::TABLE)
            ->where($query->expr()->eq('pid', $this->currentPid));

        if ($keepItemIds && !empty($keepItemIds)) {
            $query->andWhere($query->expr()->notIn('uid', $keepItemIds));
        }

        $itemsToDelete = $query->executeQuery()
            ->fetchAllAssociative();


        foreach ($keepItems as $dataSet) {
            $cmd[self::TABLE][$dataSet['uid']]['undelete'] = 1;
        }
        foreach ($itemsToDelete as $dataSet) {
            $cmd[self::TABLE][$dataSet['uid']]['delete'] = 1;
        }

        $this->dataHandler->start([], $cmd);
        $this->dataHandler->process_cmdmap();
    }
}

