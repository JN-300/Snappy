<?php

declare(strict_types=1);

namespace JeNe\Snappy\Backend\Controller;

use JeNe\Snappy\Domain\Model\Snapshot;
use JeNe\Snappy\Domain\Repository\SnapshotRepository;
use JeNe\Snappy\Services\SnapshotService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

#[AsController]
final  class SnapshotController extends ActionController
{
    /** @var ResponseFactoryInterface */
    protected $responseFactory;

    /** @var StreamFactoryInterface */
    protected $streamFactory;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly UriBuilder $backendUriBuilder,
        protected readonly SnapshotRepository $snapshotRepository,
        protected readonly SnapshotService $snapshotService
    )
    {}


    public function indexAction():ResponseInterface
    {
        $snapshots = $this->snapshotRepository->findAll();

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('snapshots', $snapshots);
        return $this->htmlResponse($moduleTemplate->render());
    }

    public function createAction():ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        return $this->htmlResponse($moduleTemplate->render());
    }

    public function storeAction(Snapshot $newSnapshot): ResponseInterface
    {
        $id = $this->request->getQueryParams()['id'];
        $snapshotStoreObject = $this->snapshotService->createStoreObject((int) $id);

        $newSnapshot->setSnapshot($snapshotStoreObject);
        /** @var SnapshotRepository $repository */
        $repository = GeneralUtility::makeInstance(\JeNe\Snappy\Domain\Repository\SnapshotRepository::class);
        $repository->add($newSnapshot);


        $uri = (string)$this->backendUriBuilder->buildUriFromRoute(
            'web_snappy', ['id' => $id])
        ;

        return $this->responseFactory->createResponse()
            ->withHeader('Location', $uri);
    }

    public function restoreAction(Snapshot $snapshot): ResponseInterface
    {
        $id = $this->request->getQueryParams()['id'];
        $snapshotData = $snapshot->getSnapshot();
        $this->snapshotService->restoreSnapshot($snapshotData);
        $uri = (string)$this->backendUriBuilder->buildUriFromRoute(
            'web_layout', ['id' => $id])
        ;
        $this->addFlashMessage('done');


        return new RedirectResponse($uri);
    }

    public function deleteAction(Snapshot  $snapshot): ResponseInterface
    {
        $id = $this->request->getQueryParams()['id'];
        $this->snapshotRepository->remove($snapshot);
        $uri = (string)$this->backendUriBuilder->buildUriFromRoute(
            'web_snappy', ['id' => $id])
        ;

        return $this->responseFactory->createResponse()
            ->withHeader('Location', $uri);

    }
}
