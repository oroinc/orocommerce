<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Modifier;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Modifier\ConvertToFedexUnitsShippingLineItemCollectionModifier;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingDimensionsUnitTransformer;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingWeightUnitTransformer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicShippingLineItemBuilderFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrineShippingLineItemCollectionFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use PHPUnit\Framework\TestCase;

class ConvertToFedexUnitsShippingLineItemCollectionModifierTest extends TestCase
{
    /**
     * @var MeasureUnitConversion|\PHPUnit\Framework\MockObject\MockObject
     */
    private $measureUnitConverter;

    /**
     * @var FedexToShippingWeightUnitTransformer
     */
    private $weightUnitTransformer;

    /**
     * @var FedexToShippingDimensionsUnitTransformer
     */
    private $dimensionsUnitTransformer;

    /**
     * @var ShippingLineItemCollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @var ShippingLineItemBuilderFactoryInterface
     */
    private $lineItemBuilderFactory;

    /**
     * @var ConvertToFedexUnitsShippingLineItemCollectionModifier
     */
    private $modifier;

    protected function setUp(): void
    {
        $this->measureUnitConverter = $this->createMock(MeasureUnitConversion::class);
        $this->weightUnitTransformer = new FedexToShippingWeightUnitTransformer();
        $this->dimensionsUnitTransformer = new FedexToShippingDimensionsUnitTransformer();
        $this->collectionFactory = new DoctrineShippingLineItemCollectionFactory();
        $this->lineItemBuilderFactory = new BasicShippingLineItemBuilderFactory();

        $this->modifier = new ConvertToFedexUnitsShippingLineItemCollectionModifier(
            $this->measureUnitConverter,
            $this->weightUnitTransformer,
            $this->dimensionsUnitTransformer,
            $this->collectionFactory,
            $this->lineItemBuilderFactory
        );
    }

    public function testModify()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(20),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(5, 10, 8),
                ShippingLineItem::FIELD_PRODUCT => $this->createMock(Product::class),
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'item',
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                ShippingLineItem::FIELD_QUANTITY => 4,
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_QUANTITY => 10,
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 3, 10),
                ShippingLineItem::FIELD_PRODUCT => $this->createMock(Product::class),
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(5, 10, 8),
                ShippingLineItem::FIELD_WEIGHT => Weight::create(10),
                ShippingLineItem::FIELD_PRODUCT => $this->createMock(Product::class),
                ShippingLineItem::FIELD_PRODUCT_SKU => 'sku',
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                ShippingLineItem::FIELD_PRICE => Price::create(135, 'USD'),
                ShippingLineItem::FIELD_QUANTITY => 5,
            ]),
        ]);

        $this->measureUnitConverter
            ->expects(static::exactly(3))
            ->method('convertDimensions')
            ->willReturnOnConsecutiveCalls(
                null,
                null,
                Dimensions::create(1, 2, 5)
            );
        $this->measureUnitConverter
            ->expects(static::exactly(2))
            ->method('convertWeight')
            ->willReturnOnConsecutiveCalls(
                Weight::create(7),
                null
            );

        static::assertEquals(
            new DoctrineShippingLineItemCollection([
                new ShippingLineItem([
                    ShippingLineItem::FIELD_PRODUCT => $this->createMock(Product::class),
                    ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
                    ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                    ShippingLineItem::FIELD_WEIGHT => Weight::create(7),
                    ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'item',
                    ShippingLineItem::FIELD_QUANTITY => 4,
                    ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
                ]),
                new ShippingLineItem([
                    ShippingLineItem::FIELD_QUANTITY => 10,
                    ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
                    ShippingLineItem::FIELD_PRODUCT => $this->createMock(Product::class),
                    ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
                    ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                    ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
                ]),
                new ShippingLineItem([
                    ShippingLineItem::FIELD_PRODUCT => $this->createMock(Product::class),
                    ShippingLineItem::FIELD_PRODUCT_SKU => 'sku',
                    ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
                    ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createMock(ProductUnit::class),
                    ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                    ShippingLineItem::FIELD_PRICE => Price::create(135, 'USD'),
                    ShippingLineItem::FIELD_QUANTITY => 5,
                    ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 2, 5),
                    ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
                ]),
            ]),
            $this->modifier->modify($lineItems, $settings)
        );
    }
}
