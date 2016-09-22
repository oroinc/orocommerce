<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListener;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Helper\FieldHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\ChainReplacePlaceholder;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchProductIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var WebsiteSearchProductIndexerListener
     */
    private $listener;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelper;

    /**
     * @var IndexEntityEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var ChainReplacePlaceholder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $chainReplacePlaceholder;

    protected function setUp()
    {
        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->chainReplacePlaceholder = $this->getMockBuilder(ChainReplacePlaceholder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchProductIndexerListener(
            $doctrineHelper,
            $this->localizationHelper,
            $this->chainReplacePlaceholder
        );
    }

    private function setExpectations()
    {
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

        $localization1 = $this->getMock(Localization::class);
        $localization1->expects($this->any())->method('getId')->willReturn(1);
        $localization2 = $this->getMock(Localization::class);
        $localization2->expects($this->any())->method('getId')->willReturn(2);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizations')
            ->willReturn([
                $localization1,
                $localization2
            ]);

        $this->event = new IndexEntityEvent(Product::class, [$product], []);
    }

    public function testOnWebsiteSearchIndexProductClass()
    {
        $this->setExpectations();
        $this->listener->onWebsiteSearchIndex($this->event);

        $expected[1] = [
            'text' => [
                'sku' => 'sku123',
                'status' => Product::STATUS_ENABLED,
                'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
                'title_1' => 'Name',
                'description_1' => '<h1>Description</h1>&nbsp;&nbsp;<p>Product information</p>',
                'short_desc_1' => 'Short description',
                'all_text_1' => 'Name <h1>Description</h1>&nbsp;&nbsp;<p>Product information</p> Short description',
                'title_2' => 'Nazwa',
                'description_2' => '<h1>Opis</h1><p>Informacje o produkcie</p>',
                'short_desc_2' => 'Krótki opis',
                'all_text_2' => 'Nazwa <h1>Opis</h1><p>Informacje o produkcie</p> Krótki opis'
            ]
        ];

        $this->assertEquals($expected, $this->event->getEntitiesData());
    }

    public function testOnWebsiteSearchIndexNotSupportedClass()
    {
        $this->event = new IndexEntityEvent(\stdClass::class, [1], []);
        $this->listener->onWebsiteSearchIndex($this->event);
        $this->assertEquals([], $this->event->getEntitiesData());
    }
}
