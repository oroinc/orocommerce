<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener\WebsiteSearchTerm\ContentBlock;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\ContentBlock\AddContentBlockToSearchTermViewPageListener;
use Oro\Bundle\CMSBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddContentBlockToSearchTermViewPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddContentBlockToSearchTermViewPageListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddContentBlockToSearchTermViewPageListener();
    }

    public function testOnEntityViewWhenBlankSearchTerm(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotModify(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('redirect');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $contentBlock = new ContentBlock();
        $searchTerm = (new SearchTermStub())
            ->setActionType('modify')
            ->setContentBlock($contentBlock);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $contentBlockData = 'content block data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroCMS/SearchTerm/content_block_field.html.twig',
                ['entity' => $searchTerm->getContentBlock()]
            )
            ->willReturn($contentBlockData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'contentBlock' => $contentBlockData,
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
        $contentBlock = new ContentBlock();
        $searchTerm = (new SearchTermStub())
            ->setActionType('modify')
            ->setContentBlock($contentBlock);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $contentBlockData = 'content block data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroCMS/SearchTerm/content_block_field.html.twig',
                ['entity' => $searchTerm->getContentBlock()]
            )
            ->willReturn($contentBlockData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'sampleField' => 'sample data',
                                    'contentBlock' => $contentBlockData,
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
