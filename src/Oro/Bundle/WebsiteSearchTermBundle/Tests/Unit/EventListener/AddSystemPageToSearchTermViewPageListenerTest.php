<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\NavigationBundle\Provider\RouteTitleProvider;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\EventListener\AddSystemPageToSearchTermViewPageListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddSystemPageToSearchTermViewPageListenerTest extends TestCase
{
    private RouteTitleProvider|MockObject $routeTitleProvider;

    private Environment|MockObject $environment;

    private AddSystemPageToSearchTermViewPageListener $listener;

    protected function setUp(): void
    {
        $this->routeTitleProvider = $this->createMock(RouteTitleProvider::class);
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddSystemPageToSearchTermViewPageListener($this->routeTitleProvider);
    }

    public function testOnEntityViewWhenBlankSearchTerm(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirectSystemPage(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('redirect');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirect(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('modify')->setRedirectActionType('system_page');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $systemPage = 'sample_route';
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('system_page')
            ->setRedirectSystemPage($systemPage);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $systemPageTitle = 'sample title';
        $this->routeTitleProvider
            ->expects(self::once())
            ->method('getTitle')
            ->with($searchTerm->getRedirectSystemPage(), 'frontend_menu')
            ->willReturn($systemPageTitle);

        $systemPageData = 'system page data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroWebsiteSearchTerm/SearchTerm/redirect_system_page_field.html.twig',
                ['systemPageTitle' => $systemPageTitle, 'entity' => $searchTerm]
            )
            ->willReturn($systemPageData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'redirectSystemPage' => $systemPageData,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $scrollData->getData()
        );
    }

    public function testOnEntityViewWhenNotEmptyScrollData(): void
    {
        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'action' => [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => ['sampleField' => 'sample data'],
                        ],
                    ],
                ],
            ],
        ]);
        $systemPage = 'sample_route';
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('system_page')
            ->setRedirectSystemPage($systemPage);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $systemPageTitle = 'sample title';
        $this->routeTitleProvider
            ->expects(self::once())
            ->method('getTitle')
            ->with($searchTerm->getRedirectSystemPage(), 'frontend_menu')
            ->willReturn($systemPageTitle);

        $systemPageData = 'system page data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroWebsiteSearchTerm/SearchTerm/redirect_system_page_field.html.twig',
                ['systemPageTitle' => $systemPageTitle, 'entity' => $searchTerm]
            )
            ->willReturn($systemPageData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'sampleField' => 'sample data',
                                    'redirectSystemPage' => $systemPageData,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $scrollData->getData()
        );
    }
}
