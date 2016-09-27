<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListener;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchProductIndexerListener
     */
    private $listener;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepository;

    /**
     * @var AbstractWebsiteLocalizationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteLocalizationProvider;

    /**
     * @var IndexEntityEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteLocalizationProvider = $this->getMockBuilder(AbstractWebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchProductIndexerListener(
            $this->doctrineHelper,
            $this->websiteLocalizationProvider
        );

        $this->event = $this->getMockBuilder(IndexEntityEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->productRepository, $this->websiteLocalizationProvider, $this->listener, $this->event);
    }

    /**
     * @param string $entityClassName
     */
    private function initializeOnWebsiteSearchIndexTest($entityClassName)
    {
        $this->event->expects($this->once())->method('getEntityClass')->willReturn($entityClassName);

        $this->event->expects($this->once())->method('getEntityIds')->willReturn([1]);

        $this->event->expects($this->once())->method('getContext')->willReturn([
            AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 1,
        ]);

        $product = $this->getMockBuilder(Product::class)
            ->setMethods([
                'getId',
                'getSku',
                'getStatus',
                'getInventoryStatus',
                'getName',
                'getDescription',
                'getShortDescription',
            ])
            ->getMock();

        $inventoryStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryStatus->expects($this->once())->method('getId')->willReturn(Product::INVENTORY_STATUS_IN_STOCK);

        $product->expects($this->any())->method('getId')->willReturn(1);
        $product->expects($this->once())->method('getSku')->willReturn('sku123');
        $product->expects($this->once())->method('getStatus')->willReturn(Product::STATUS_ENABLED);
        $product->expects($this->once())->method('getInventoryStatus')->willReturn($inventoryStatus);
        $product->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls(
                'Name',
                'Nazwa'
            );
        $product->expects($this->exactly(2))
            ->method('getDescription')
            ->willReturnOnConsecutiveCalls(
                '<h1>Description</h1>&nbsp;&nbsp;<p>Product information</p>',
                '<h1>Opis</h1><p>Informacje o produkcie</p>'
            );
        $product->expects($this->exactly(2))
            ->method('getShortDescription')
            ->willReturnOnConsecutiveCalls(
                'Short description',
                'Krótki opis'
            );

        $this->productRepository->expects($this->once())->method('getProductsByIds')->willReturn([$product]);

        $localization1 = $this->getMock(Localization::class);
        $localization1->expects($this->any())->method('getId')->willReturn(1);
        $localization2 = $this->getMock(Localization::class);
        $localization2->expects($this->any())->method('getId')->willReturn(2);

        $this->websiteLocalizationProvider
            ->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn([
                $localization1,
                $localization2
            ]);
    }

    public function testOnWebsiteSearchIndexProductClass()
    {
        $this->initializeOnWebsiteSearchIndexTest(Product::class);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($this->productRepository);

        $this->event
            ->expects($this->exactly(11))
            ->method('addField')
            ->withConsecutive(
                [1, 'text', 'sku', 'sku123'],
                [1, 'text', 'status', Product::STATUS_ENABLED],
                [1, 'text', 'inventory_status', Product::INVENTORY_STATUS_IN_STOCK],
                [1, 'text', 'title_1', 'Name'],
                [1, 'text', 'description_1', 'Description Product information'],
                [1, 'text', 'short_desc_1', 'Short description'],
                [1, 'text', 'all_text_1', 'Name Description Product information Short description'],
                [1, 'text', 'title_2', 'Nazwa'],
                [1, 'text', 'description_2', 'Opis Informacje o produkcie'],
                [1, 'text', 'short_desc_2', 'Krótki opis'],
                [1, 'text', 'all_text_2', 'Nazwa Opis Informacje o produkcie Krótki opis']
            );

        $this->listener->onWebsiteSearchIndex($this->event);
    }

    public function testOnWebsiteSearchIndexNotSupportedClass()
    {
        $this->event->expects($this->once())->method('getEntityClass')->willReturn('stdClass');

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $this->event->expects($this->never())->method('addField');

        $this->listener->onWebsiteSearchIndex($this->event);
    }
}
