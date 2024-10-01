<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Modifier;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Modifier\ConvertToFedexUnitsShippingLineItemCollectionModifier;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingDimensionsUnitTransformer;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingWeightUnitTransformer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConvertToFedexUnitsShippingLineItemCollectionModifierTest extends TestCase
{
    private MeasureUnitConversion|MockObject $measureUnitConverter;

    private ConvertToFedexUnitsShippingLineItemCollectionModifier $modifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->measureUnitConverter = $this->createMock(MeasureUnitConversion::class);

        $this->modifier = new ConvertToFedexUnitsShippingLineItemCollectionModifier(
            $this->measureUnitConverter,
            new FedexToShippingWeightUnitTransformer(),
            new FedexToShippingDimensionsUnitTransformer()
        );
    }

    public function testModify(): void
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        $lineItems = new ArrayCollection([
            (new ShippingLineItem(
                $this->createMock(ProductUnit::class),
                4,
                $this->createMock(ProductHolderInterface::class)
            ))
                ->setProductUnitCode('item')
                ->setProduct($this->createMock(Product::class))
                ->setWeight(Weight::create(20))
                ->setDimensions(Dimensions::create(5, 10, 8)),
            (new ShippingLineItem(
                $this->createMock(ProductUnit::class),
                10,
                $this->createMock(ProductHolderInterface::class)
            ))
                ->setProductUnitCode('each')
                ->setProduct($this->createMock(Product::class))
                ->setDimensions(Dimensions::create(1, 3, 10)),
            (new ShippingLineItem(
                $this->createMock(ProductUnit::class),
                5,
                $this->createMock(ProductHolderInterface::class)
            ))
                ->setProductUnitCode('each')
                ->setProduct($this->createMock(Product::class))
                ->setProductSku('sku')
                ->setPrice(Price::create(135, 'USD'))
                ->setWeight(Weight::create(10))
                ->setDimensions(Dimensions::create(5, 10, 8)),
        ]);

        $this->measureUnitConverter
            ->expects(self::exactly(3))
            ->method('convertDimensions')
            ->willReturnOnConsecutiveCalls(
                null,
                null,
                Dimensions::create(1, 2, 5)
            );
        $this->measureUnitConverter
            ->expects(self::exactly(2))
            ->method('convertWeight')
            ->willReturnOnConsecutiveCalls(
                Weight::create(7),
                null
            );

        self::assertEquals(
            new ArrayCollection([
                (new ShippingLineItem(
                    $this->createMock(ProductUnit::class),
                    4,
                    $this->createMock(ProductHolderInterface::class)
                ))
                    ->setProductUnitCode('item')
                    ->setProduct($this->createMock(Product::class))
                    ->setWeight(Weight::create(7))
                    ->setDimensions(null),
                (new ShippingLineItem(
                    $this->createMock(ProductUnit::class),
                    10,
                    $this->createMock(ProductHolderInterface::class)
                ))
                    ->setProductUnitCode('each')
                    ->setProduct($this->createMock(Product::class))
                    ->setWeight(null)
                    ->setDimensions(null),
                (new ShippingLineItem(
                    $this->createMock(ProductUnit::class),
                    5,
                    $this->createMock(ProductHolderInterface::class)
                ))
                    ->setProductUnitCode('each')
                    ->setProduct($this->createMock(Product::class))
                    ->setProductSku('sku')
                    ->setPrice(Price::create(135, 'USD'))
                    ->setWeight(null)
                    ->setDimensions(Dimensions::create(1, 2, 5)),
            ]),
            $this->modifier->modify($lineItems, $settings)
        );
    }
}
