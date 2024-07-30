<?php

declare(strict_types=1);

namespace JeNe\Snappy\EventListener;

use JeNe\Snappy\Domain\Repository\SnapshotRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownDivider;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownHeader;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class ModifyButtonBarEventListener
{
    protected ?ServerRequestInterface $request = null;
    public function __construct(
        private readonly SnapshotRepository $snapshotRepository
    )
    {
        $this->request = ServerRequestFactory::fromGlobals();
    }

    public function __invoke(\TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent $event): void
    {
        $pageId = $this->request->getQueryParams()['id'] ?? null;

        $pageInfo = BackendUtility::readPageAccess($pageId, $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW));

//        DebuggerUtility::var_dump($pageInfo['doktype']);
        if (!$pageId || $pageId <= 0 || $pageInfo['doktype'] !== 1) return;


        $buttons = $event->getButtons();
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $buttonBar = $event->getButtonBar();
        $dropDown = $buttonBar->makeDropDownButton()
            ->setLabel('SnapShot')
            ->setTitle('Snapshot')
            ->setShowLabelText(true)
            ->setIcon($iconFactory->getIcon('apps-toolbar-menu-workspace'))

            ->addItem(
                GeneralUtility::makeInstance(DropDownItem::class)
                ->setLabel('Create Snapshot')
                ->setHref((string)$uriBuilder->buildUriFromRoute('web_snappy.Snapshot_create', ['id' => $pageId]))
                ->setIcon($iconFactory->getIcon('actions-logout'))
            )

            ;

        $snapshotCount = $this->snapshotRepository->findAll()->count();

        if ($snapshotCount >0) {
            $dropDown
                ->addItem(GeneralUtility::makeInstance(DropDownDivider::class))
                ->addItem(GeneralUtility::makeInstance(DropDownHeader::class)->setLabel('Re-Import'))
                ;
        }
        $lastSnapshots = $this->snapshotRepository->createQuery()
            ->setOrderings(['crdate' => 'DESC'])
            ->setLimit(5)
            ->execute();


        foreach ($lastSnapshots as $snapshot) {
            $dropDown
                ->addItem(
                    GeneralUtility::makeInstance(DropDownItem::class)
                        ->setLabel($snapshot->title)
                        ->setHref((string)$uriBuilder->buildUriFromRoute('web_snappy.Snapshot_restore', ['id' => $pageId, 'snapshot' => $snapshot->getUid()]))
                        ->setIcon($iconFactory->getIcon('actions-login'))

                )
            ;
        }
        if ($snapshotCount > 5) {
            $dropDown
                ->addItem(
                    GeneralUtility::makeInstance(DropDownItem::class)
                        ->setLabel('... More')
    //                    ->setHref('#snapshot')
                        ->setHref((string)$uriBuilder->buildUriFromRoute('web_snappy', ['id' => $pageId]))
//                        ->setIcon($iconFactory->getIcon('actions-login'))

                )
            ;
        }


        $button = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setTitle('Create Snapshot')
            ->setIcon($iconFactory->getIcon('actions-logout'))
            ->setDataAttributes([
                'snapshot' => 'create',
                'dispatch-action' => 'TYPO3.InfoWindow.showItem',
                'dispatch-args-list' => 'be_users,1',
            ])
        ;
        $html = '
            <script>
                
            
            </script>
        ';
        $buttons[ButtonBar::BUTTON_POSITION_RIGHT][self::class][] = $html. $button . $dropDown;

        $event->setButtons($buttons);

    }
}
