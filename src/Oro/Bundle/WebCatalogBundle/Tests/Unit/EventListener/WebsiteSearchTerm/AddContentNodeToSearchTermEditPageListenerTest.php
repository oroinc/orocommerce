<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm\AddContentNodeToSearchTermEditPageListener;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class AddContentNodeToSearchTermEditPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddContentNodeToSearchTermEditPageListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddContentNodeToSearchTermEditPageListener();
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

        $contentNodeData = 'content node data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroWebCatalog/SearchTerm/redirect_content_node_form.html.twig',
                ['form' => $formView]
            )
            ->willReturn($contentNodeData);

        $this->listener->onEntityEdit($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'redirect301' => 'sample data',
                                    'redirectContentNode' => $contentNodeData,
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
