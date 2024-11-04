<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm\ProductCollection;

// phpcs:disable Generic.Files.LineLength.TooLong
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection\AddProductCollectionToSearchTermViewPageListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddProductCollectionToSearchTermViewPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddProductCollectionToSearchTermViewPageListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddProductCollectionToSearchTermViewPageListener();
    }

    public function testOnEntityViewWhenBlankSearchTerm(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotModifyProductCollection(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('modify');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotModify(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('modify')->setRedirectActionType('product_collection');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $productCollection = new Segment();
        $searchTerm = (new SearchTermStub())
            ->setActionType('modify')
            ->setModifyActionType('product_collection')
            ->setProductCollectionSegment($productCollection);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $productCollectionData = 'product collection data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroProduct/SearchTerm/product_collection_field.html.twig',
                ['entity' => $searchTerm->getProductCollectionSegment()]
            )
            ->willReturn($productCollectionData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'productCollection' => $productCollectionData,
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
        $productCollection = new Segment();
        $searchTerm = (new SearchTermStub())
            ->setActionType('modify')
            ->setModifyActionType('product_collection')
            ->setProductCollectionSegment($productCollection);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $productCollectionData = 'product collection data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroProduct/SearchTerm/product_collection_field.html.twig',
                ['entity' => $searchTerm->getProductCollectionSegment()]
            )
            ->willReturn($productCollectionData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'sampleField' => 'sample data',
                                    'productCollection' => $productCollectionData,
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
