<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm\AddContentNodeToSearchTermViewPageListener;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddContentNodeToSearchTermViewPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddContentNodeToSearchTermViewPageListener $listener;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddContentNodeToSearchTermViewPageListener();
    }

    public function testOnEntityViewWhenBlankSearchTerm(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirectContentNode(): void
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
        $searchTerm = (new SearchTerm())->setActionType('modify')->setRedirectActionType('content_node');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $contentNode = new ContentNode();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('content_node')
            ->setRedirectContentNode($contentNode);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $contentNodeData = 'content node data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroWebCatalog/SearchTerm/redirect_content_node_field.html.twig',
                ['entity' => $searchTerm->getRedirectContentNode()]
            )
            ->willReturn($contentNodeData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
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
        $contentNode = new ContentNode();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('content_node')
            ->setRedirectContentNode($contentNode);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $contentNodeData = 'content node data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroWebCatalog/SearchTerm/redirect_content_node_field.html.twig',
                ['entity' => $searchTerm->getRedirectContentNode()]
            )
            ->willReturn($contentNodeData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'sampleField' => 'sample data',
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
