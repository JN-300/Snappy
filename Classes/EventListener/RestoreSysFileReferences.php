<?php

declare(strict_types=1);

namespace JeNe\Snappy\EventListener;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class RestoreSysFileReferences
{
    const TABLE = 'sys_file_reference';
    private int $currentPid;


    public function __construct(
        private DataHandler $dataHandler
    )
    {
    }

    public function __invoke(\JeNe\Snappy\Event\SnapshotAfterRestoringPageDataEvent $event): void
    {
        $this->currentPid = $event->getSnapshotStoreObject()->pageData['uid'];
        $data = $event->getSnapshotStoreObject()->getElementData(self::TABLE);
        $groupedData = $this->groupData($data);
        foreach($groupedData as $table => $items)
        {
            $tableData = $event->getSnapshotStoreObject()->getElementData($table);
            foreach ($tableData as &$tableDatum) {
                $tableId = $tableDatum['uid'];
                if (!in_array($tableId, array_keys($items))) continue;
                foreach ($items[$tableId] as $fieldKey => $fileReferences)
                {
                    $tableDatum[$fieldKey] = implode(',', array_keys($fileReferences));
                }
            }
            unset($tableDatum);

            $event->getSnapshotStoreObject()->addElementData($table, $tableData);
        }

        $data = $this->prepareData($data);

        $this->processHideAllNotAvailableItems($data);
        $this->processData($data);
    }


    private function processHideAllNotAvailableItems(?array $data)
    {
        $keepItems = array_filter($data, fn($item) => $item['deleted'] === 0);
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

        foreach ($itemsToDelete as $item)
        {
            $cmd[self::TABLE][$item['uid']]['delete'] = 1;
        }

        $this->dataHandler->start([], $cmd);
        $this->dataHandler->process_cmdmap();
    }
    private function groupData($data):array
    {
        $groupedData = [];
        $data = array_filter($data, fn($item) => $item['deleted'] === 0);
        foreach ($data as $dataset) {
            $groupedData[$dataset['tablenames']][$dataset['uid_foreign']][$dataset['fieldname']][$dataset['uid']] = $dataset;
        }

        return $groupedData;
    }

    private function processData(?array $updateData)
    {
        $cmd = [];
        foreach ($updateData as $dataSet) {
            $deleteStatus = ($dataSet['deleted'] == 1)
                ? 'delete'
                : 'undelete'
            ;
            $cmd[self::TABLE][$dataSet['uid']][$deleteStatus] = 1;
            $data[self::TABLE][$dataSet['uid']] = $dataSet;
        }

        $this->dataHandler->start($data, $cmd);
        $this->dataHandler->process_cmdmap();
        $this->dataHandler->process_datamap();
    }


    private function prepareData(?array $data):?array
    {

        return $data;
    }
}
