<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Component\Testing\Unit\EntityTrait;

class PackageProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var MeasureUnitConversion|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $measureUnitConversion;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var  PackageProvider
     */
    protected $packageProvider;

    protected function setUp()
    {
        /** @var ManagerRegistry | \PHPUnit_Framework_MockObject_MockObject $doctrine */
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();

        $this->measureUnitConversion = $this->getMockBuilder(MeasureUnitConversion::class)
            ->disableOriginalConstructor()->getMock();
        $this->measureUnitConversion->expects(static::any())->method('convert')->willReturnCallback(
            function () {
                $args = func_get_args();

                return $args[0];
            }
        );

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->packageProvider =
            new PackageProvider($this->registry, $this->measureUnitConversion, $this->localizationHelper);
    }

    /**
     * @param int $lineItemCnt
     * @param int $productWeight
     * @param Package[]|null $expectedPackages
     *
     * @dataProvider packagesDataProvider
     */
    public function testCreatePackages($lineItemCnt, $productWeight, $expectedPackages)
    {
        $this->localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')->willReturn('product name');

        $lineItems = [];
        $allProductsShippingOptions = [];
        for ($i = 1; $i <= $lineItemCnt; $i++) {
            /** @var Product $product */
            $product = $this->getEntity(Product::class, ['id' => $i]);

            $lineItems[] = new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT           => $product,
                ShippingLineItem::FIELD_QUANTITY          => 1,
                ShippingLineItem::FIELD_PRODUCT_UNIT      => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'test1',
                ShippingLineItem::FIELD_ENTITY_IDENTIFIER => 1,
                ShippingLineItem::FIELD_DIMENSIONS        => Dimensions::create(7, 7, 7,
                    (new LengthUnit())->setCode('inch')),
                ShippingLineItem::FIELD_WEIGHT            => Weight::create($productWeight, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'lbs']
                )),
            ]);

            /** @var ProductShippingOptions $productShippingOptions */
            $allProductsShippingOptions[] = $this->getEntity(
                ProductShippingOptions::class,
                [
                    'id'          => 42,
                    'product'     => $product,
                    'productUnit' => $this->getEntity(
                        ProductUnit::class,
                        ['code' => 'test1']
                    ),
                    'dimensions'  => Dimensions::create(7, 7, 7, (new LengthUnit())->setCode('inch')),
                    'weight'      => Weight::create($productWeight, $this->getEntity(
                        WeightUnit::class,
                        ['code' => 'kg']
                    )),
                ]
            );
        }

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($lineItems),
        ]);

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findBy')->willReturn($allProductsShippingOptions);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

        $packages = $this->packageProvider->createPackages($context->getLineItems());

        static::assertEquals($expectedPackages, $packages);
    }

    /**
     * @return array
     */
    public function packagesDataProvider()
    {
        return [
            'OnePackage'  => [
                'lineItemCnt'      => 2,
                'productWeight'    => 15,
                'expectedPackages' => [
                    (new Package())->setWeight(30)->setContents('product name,product name'),
                ],
            ],
            'TwoPackages' => [
                'lineItemCnt'      => 2,
                'productWeight'    => 30,
                'expectedPackages' => [
                    (new Package())->setWeight(30)->setContents('product name'),
                    (new Package())->setWeight(30)->setContents('product name'),
                ],
            ],
            'TooBigToFit' => [
                'lineItemCnt'      => 2,
                'productWeight'    => PackageProvider::MAX_PACKAGE_WEIGHT_KGS + 1,
                'expectedPackages' => null,
            ],
            'NoPackages'  => [
                'lineItemCnt'      => 0,
                'productWeight'    => 30,
                'expectedPackages' => null,
            ],
        ];
    }
}
