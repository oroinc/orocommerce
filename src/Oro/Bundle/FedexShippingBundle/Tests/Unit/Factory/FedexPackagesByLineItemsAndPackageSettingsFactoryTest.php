<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\FedexShippingBundle\Builder\ShippingPackagesByLineItemBuilderInterface;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageByShippingPackageOptionsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackagesByLineItemsAndPackageSettingsFactory;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\TestCase;

class FedexPackagesByLineItemsAndPackageSettingsFactoryTest extends TestCase
{
    /**
     * @var ShippingPackagesByLineItemBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $packagesBuilder;

    /**
     * @var FedexPackageByShippingPackageOptionsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fedexPackageFactory;

    /**
     * @var FedexPackagesByLineItemsAndPackageSettingsFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->packagesBuilder = $this->createMock(ShippingPackagesByLineItemBuilderInterface::class);
        $this->fedexPackageFactory = $this->createMock(FedexPackageByShippingPackageOptionsFactoryInterface::class);

        $this->factory = new FedexPackagesByLineItemsAndPackageSettingsFactory(
            $this->packagesBuilder,
            $this->fedexPackageFactory
        );
    }

    public function testCreateLineItemCannotBeAdded()
    {
        $packageSettings = new FedexPackageSettings(0, 0, 0);
        $lineItemCollection = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(0),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(0, 0, 0),
            ]),
        ]);

        $this->packagesBuilder
            ->expects(static::once())
            ->method('init')
            ->with($packageSettings);

        $this->packagesBuilder
            ->expects(static::once())
            ->method('addLineItem')
            ->willReturn(false);

        static::assertEquals([], $this->factory->create($lineItemCollection, $packageSettings));
    }

    public function testCreateNoWeight()
    {
        $packageSettings = new FedexPackageSettings(0, 0, 0);
        $lineItemCollection = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(0, 0, 0),
            ]),
        ]);

        static::assertEquals([], $this->factory->create($lineItemCollection, $packageSettings));
    }

    public function testCreateNoDimensions()
    {
        $packageSettings = new FedexPackageSettings(0, 0, 0);
        $lineItemCollection = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(0),
            ]),
        ]);

        static::assertEquals([], $this->factory->create($lineItemCollection, $packageSettings));
    }

    public function testCreate()
    {
        $lineItems = [
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(0),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(0, 0, 0),
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_WEIGHT => Weight::create(2),
                ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(3, 4, 5),
            ]),
        ];
        $lineItemCollection = new DoctrineShippingLineItemCollection($lineItems);
        $packageSettings = new FedexPackageSettings(0, 0, 0);
        $packageOptions = [
            new ShippingPackageOptions(Dimensions::create(0, 0, 0), Weight::create(0)),
            new ShippingPackageOptions(Dimensions::create(0, 0, 0), Weight::create(0)),
        ];

        $this->packagesBuilder
            ->expects(static::once())
            ->method('init')
            ->with($packageSettings);
        $this->packagesBuilder
            ->expects(static::exactly(2))
            ->method('addLineItem')
            ->withConsecutive([$lineItems[0]], [$lineItems[1]])
            ->willReturn(true);
        $this->packagesBuilder
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($packageOptions);

        $fedexPackages = [['1'], ['2']];
        $this->fedexPackageFactory
            ->expects(static::exactly(2))
            ->method('create')
            ->withConsecutive([$packageOptions[0]], [$packageOptions[1]])
            ->willReturnOnConsecutiveCalls($fedexPackages[0], $fedexPackages[1]);

        static::assertSame($fedexPackages, $this->factory->create($lineItemCollection, $packageSettings));
    }
}
