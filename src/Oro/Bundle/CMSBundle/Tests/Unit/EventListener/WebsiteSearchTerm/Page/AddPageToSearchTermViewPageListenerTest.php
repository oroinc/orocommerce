<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener\WebsiteSearchTerm\Page;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\Page\AddPageToSearchTermViewPageListener;
use Oro\Bundle\CMSBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddPageToSearchTermViewPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddPageToSearchTermViewPageListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddPageToSearchTermViewPageListener();
    }

    public function testOnEntityViewWhenBlankSearchTerm(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirectCmsPage(): void
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
        $searchTerm = (new SearchTerm())->setActionType('modify')->setRedirectActionType('cms_page');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $cmsPage = new Page();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('cms_page')
            ->setRedirectCmsPage($cmsPage);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $cmsPageData = 'cms page data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroCMS/SearchTerm/redirect_cms_page_field.html.twig',
                ['entity' => $searchTerm->getRedirectCmsPage()]
            )
            ->willReturn($cmsPageData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'redirectCmsPage' => $cmsPageData,
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
        $cmsPage = new Page();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('cms_page')
            ->setRedirectCmsPage($cmsPage);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $cmsPageData = 'cms page data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroCMS/SearchTerm/redirect_cms_page_field.html.twig',
                ['entity' => $searchTerm->getRedirectCmsPage()]
            )
            ->willReturn($cmsPageData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'sampleField' => 'sample data',
                                    'redirectCmsPage' => $cmsPageData,
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
