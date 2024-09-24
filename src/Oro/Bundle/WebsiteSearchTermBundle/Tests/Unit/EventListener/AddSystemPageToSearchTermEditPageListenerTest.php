<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\EventListener\AddSystemPageToSearchTermEditPageListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class AddSystemPageToSearchTermEditPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddSystemPageToSearchTermEditPageListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddSystemPageToSearchTermEditPageListener();
    }

    public function testOnEntityEditWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityEdit($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityEditWhenNoRedirect301(): void
    {
        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => ['action' => []],
        ]);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityEdit($event);

        self::assertEquals([ScrollData::DATA_BLOCKS => ['action' => []]], $scrollData->getData());
    }

    public function testOnEntityEdit(): void
    {
        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'action' => [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => ['redirect301' => 'sample data'],
                        ],
                    ],
                ],
            ],
        ]);
        $formView = $this->createMock(FormView::class);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm(), $formView);

        $systemPageData = 'system page data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroWebsiteSearchTerm/SearchTerm/redirect_system_page_form.html.twig',
                ['form' => $formView]
            )
            ->willReturn($systemPageData);

        $this->listener->onEntityEdit($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'redirect301' => 'sample data',
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
