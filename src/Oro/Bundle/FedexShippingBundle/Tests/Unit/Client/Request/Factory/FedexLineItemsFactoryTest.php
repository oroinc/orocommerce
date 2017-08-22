<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\Request\Factory;

use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexLineItemsFactory;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Provider\ShippingLineItemsByContextAndSettingsProviderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\TestCase;

class FedexLineItemsFactoryTest extends TestCase
{
    /**
     * @var ShippingLineItemsByContextAndSettingsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsProvider;

    /**
     * @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var FedexLineItemsFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->lineItemsProvider = $this->createMock(ShippingLineItemsByContextAndSettingsProviderInterface::class);
        $this->context = $this->createMock(ShippingContextInterface::class);

        $this->factory = new FedexLineItemsFactory($this->lineItemsProvider);
    }

    public function testCreateItemWeightTooBig()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        $lineItems = [
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(71),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(2, 4, 7),
            ])
        ];

        $this->lineItemsProvider
            ->expects(static::once())
            ->method('get')
            ->with($settings, $this->context)
            ->willReturn($lineItems);

        static::assertEquals(new FedexRequest(), $this->factory->create($settings, $this->context));
    }

    public function testCreateItemLengthTooBig()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_LB);

        $lineItems = [
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(50),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(120, 4, 7),
            ])
        ];

        $this->lineItemsProvider
            ->expects(static::once())
            ->method('get')
            ->with($settings, $this->context)
            ->willReturn($lineItems);

        static::assertEquals(new FedexRequest(), $this->factory->create($settings, $this->context));
    }

    public function testCreateItemGirthTooBig()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        $lineItems = [
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(50),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(19.2, 100, 100),
            ])
        ];

        $this->lineItemsProvider
            ->expects(static::once())
            ->method('get')
            ->with($settings, $this->context)
            ->willReturn($lineItems);

        static::assertEquals(new FedexRequest(), $this->factory->create($settings, $this->context));
    }

    public function testCreateInKg()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        $lineItems = [
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(50),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(20, 10, 15),
                ShippingLineItem::FIELD_QUANTITY => 1,
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(6),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(10, 15, 30),
                ShippingLineItem::FIELD_QUANTITY => 2,
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(10),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(100, 50, 100),
                ShippingLineItem::FIELD_QUANTITY => 2,
            ]),
        ];

        $this->lineItemsProvider
            ->expects(static::once())
            ->method('get')
            ->with($settings, $this->context)
            ->willReturn($lineItems);

        static::assertEquals(
            new FedexRequest([
                $this->createPackage($settings, 62, 40, 40, 75),
                $this->createPackage($settings, 10, 100, 50, 100),
                $this->createPackage($settings, 10, 100, 50, 100),
            ]),
            $this->factory->create($settings, $this->context)
        );
    }

    public function testCreateInLb()
    {
        $settings = new FedexIntegrationSettings();
        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_LB);

        $lineItems = [
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(100),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(15, 25, 15),
                ShippingLineItem::FIELD_QUANTITY => 1,
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(40),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(5, 10, 10),
                ShippingLineItem::FIELD_QUANTITY => 2,
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(30),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(60, 25, 25),
                ShippingLineItem::FIELD_QUANTITY => 2,
            ]),
        ];

        $this->lineItemsProvider
            ->expects(static::once())
            ->method('get')
            ->with($settings, $this->context)
            ->willReturn($lineItems);

        static::assertEquals(
            new FedexRequest([
                $this->createPackage($settings, 140, 20, 35, 25),
                $this->createPackage($settings, 40, 5, 10, 10),
                $this->createPackage($settings, 30, 60, 25, 25),
                $this->createPackage($settings, 30, 60, 25, 25),
            ]),
            $this->factory->create($settings, $this->context)
        );
    }

    /**
     * @param FedexIntegrationSettings $settings
     * @param float                    $weight
     * @param float                    $length
     * @param float                    $width
     * @param float                    $height
     *
     * @return array
     */
    private function createPackage(
        FedexIntegrationSettings $settings,
        float $weight,
        float $length,
        float $width,
        float $height
    ): array {
        return [
            'GroupPackageCount' => 1,
            'Weight' => [
                'Value' => $weight,
                'Units' => $settings->getUnitOfWeight(),
            ],
            'Dimensions' => [
                'Length' => $length,
                'Width' => $width,
                'Height' => $height,
                'Units' => $settings->getDimensionsUnit(),
            ],
        ];
    }
}
