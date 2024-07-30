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

        /**
         * This is a little bit ugly but helps to hold the data integration between sys_file_references and other tables
         * At first we must group the stored data by tablename|uid_foreign|fieldname
         */
        $groupedData = $this->groupData($data);
        /**
         * First iteration by tablename / element tyoe
         * @var  $table
         * @var  $items
         */
        foreach($groupedData as $table => $items)
        {
            /**
             * Load the stored date from snapshot for the current element type (e.g. tt_content)
             */
            $tableData = $event->getSnapshotStoreObject()->getElementData($table);
            /**
             * Iterate through the stored elements
             */
            foreach ($tableData as &$tableDatum) {
                $tableId = $tableDatum['uid'];
                if (!in_array($tableId, array_keys($items))) continue;
                /**
                 * iterate through the sys_file_refrences for the current element
                 * and attach them to the stored Snapshot object
                 * so the datahandler in the dedicated Restore Events  will not delete the file references
                 */
                foreach ($items[$tableId] as $fieldKey => $fileReferences)
                {
                    $tableDatum[$fieldKey] = implode(',', array_keys($fileReferences));
                }
            }
            unset($tableDatum);
            // restore element data to the snapshot object
            $event->getSnapshotStoreObject()->addElementData($table, $tableData);
        }

        /**
         * After prepare other data now handle the sys_file_reference data
         */
        $data = $this->prepareData($data);

        $this->processHideAllNotAvailableItems($data);
        $this->processData($data);
    }


    /**
     * (Soft)Delete all items, which are not in the current snapshot
     *
     * @param array|null $data
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
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

    /**
     * Group Elements by
     * - type (tablenames)
     * - element id (uid_foreign)
     * - relation name (fieldname)
     *
     * @return array
     */
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
