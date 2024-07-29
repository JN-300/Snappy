<?php

namespace JeNe\Snappy\Services;

use Doctrine\DBAL\Exception;
use JeNe\Snappy\DTO\SnapshotStoreObject;
use JeNe\Snappy\Event\SnapshotAfterLoadingPageDataEvent;
use JeNe\Snappy\Event\SnapshotAfterRestoringPageDataEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class SnapshotService implements \TYPO3\CMS\Core\SingletonInterface
{

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DataHandler $dataHandler
    )
    {}

    /**
     * @param int $pageUid
     * @return false|mixed[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function createStoreObject(int $pageUid):SnapshotStoreObject
    {
        $snapshotStoreObject = new SnapshotStoreObject($this->loadPageData($pageUid));
        /** @var SnapshotAfterLoadingPageDataEvent $event */
        $event = $this->eventDispatcher->dispatch(new SnapshotAfterLoadingPageDataEvent($snapshotStoreObject));

        return $event->getSnapshotStoreObject();
    }

    public function restoreSnapshot(SnapshotStoreObject $snapshotStoreObject):void
    {
        $pageData = $snapshotStoreObject->pageData;
        $this->restorePageData($pageData);
        /** @var SnapshotAfterRestoringPageDataEvent $event */
        $this->eventDispatcher->dispatch(new SnapshotAfterRestoringPageDataEvent($snapshotStoreObject));
    }

    /**
     * @throws Exception
     */
    private function loadPageData(int $pageUid):array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->createQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $pageUid))
            ->executeQuery()
            ->fetchAssociative()
            ;
    }

    private function restorePageData(array $pageData): void
    {
        $data['pages'][$pageData['uid']] = [
            'title' => $pageData['title']
        ];
        $this->dataHandler->start($data, []);
        $this->dataHandler->process_datamap();
    }

}