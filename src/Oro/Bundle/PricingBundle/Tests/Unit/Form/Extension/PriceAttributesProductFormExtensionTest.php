<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\ProductAttributePriceType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\ProductTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceAttributesProductFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($string) {
                return $string . '_translated';
            });

        return [
            new PreloadedExtension(
                [
                    ProductType::class => new ProductTypeStub(),
                    ProductAttributePriceCollectionType::class => new ProductAttributePriceCollectionType($translator),
                    ProductAttributePriceType::class => new ProductAttributePriceType()
                ],
                [
                    ProductTypeStub::class => [
                        new PriceAttributesProductFormExtension($this->registry, $this->aclHelper)
                    ]
                ]
            )
        ];
    }

    public function testSubmit()
    {
        $em = $this->createMock(ObjectManager::class);

        $priceRepository = $this->createMock(PriceAttributeProductPriceRepository::class);
        $priceRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $attributeRepository = $this->createMock(PriceAttributePriceListRepository::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $attributeRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceAttributePriceList::class, $attributeRepository],
                [PriceAttributeProductPrice::class, $priceRepository],
            ]);

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);
        $form = $this->factory->create(ProductType::class, new Product(), []);

        $form->submit([]);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
    }

    public function testDataAddedOnPostSetData()
    {
        $em = $this->createMock(ObjectManager::class);

        $product = new Product();
        $unit1 = (new ProductUnit())->setCode('item');
        $unit2 = (new ProductUnit())->setCode('set');
        $product
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit1))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit2));

        $priceAttribute1 = $this->getEntity(PriceAttributePriceList::class, ['id' => 1])
            ->setName('Price Attribute 1')
            ->addCurrencyByCode('USD')
            ->addCurrencyByCode('EUR');
        $priceAttribute2 = $this->getEntity(PriceAttributePriceList::class, ['id' => 2])
            ->setName('Price Attribute 2')
            ->addCurrencyByCode('USD');

        $priceRepository = $this->createMock(PriceAttributeProductPriceRepository::class);
        $price1USD = (new PriceAttributeProductPrice())->setUnit($unit1)
            ->setPrice(Price::create('0', 'USD'))
            ->setQuantity(1)
            ->setPriceList($priceAttribute1)
            ->setProduct($product);
        $price1EUR = (new PriceAttributeProductPrice())->setUnit($unit1)
            ->setPrice(Price::create('80', 'EUR'))
            ->setQuantity(1)
            ->setPriceList($priceAttribute1)
            ->setProduct($product);
        $price2USD = (new PriceAttributeProductPrice())->setUnit($unit2)
            ->setPrice(Price::create('150', 'USD'))
            ->setQuantity(1)
            ->setPriceList($priceAttribute2)
            ->setProduct($product);
        $priceRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$price1USD, $price1EUR, $price2USD]);

        $attributeRepository = $this->createMock(PriceAttributePriceListRepository::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$priceAttribute1, $priceAttribute2]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $attributeRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceAttributePriceList::class, $attributeRepository],
                [PriceAttributeProductPrice::class, $priceRepository],
            ]);
        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);

        $form = $this->factory->create(ProductType::class, $product, []);
        $expected = [
            1 => [
                $price1USD,
                $price1EUR,
                (new PriceAttributeProductPrice())
                    ->setUnit($unit2)
                    ->setPrice(Price::create(null, 'EUR'))
                    ->setQuantity(1)
                    ->setPriceList($priceAttribute1)
                    ->setProduct($product),
                (new PriceAttributeProductPrice())
                    ->setUnit($unit2)
                    ->setPrice(Price::create(null, 'USD'))
                    ->setQuantity(1)
                    ->setPriceList($priceAttribute1)
                    ->setProduct($product)
            ],
            2 => [
                $price2USD,
                (new PriceAttributeProductPrice())
                    ->setUnit($unit1)
                    ->setPrice(Price::create(null, 'USD'))
                    ->setQuantity(1)
                    ->setPriceList($priceAttribute2)
                    ->setProduct($product)
            ]
        ];

        $actual = $form->get(PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES)->getData();
        $this->assertEquals($expected, $actual);
    }

    public function testPostSubmitNewPricesPersisted()
    {
        $em = $this->createMock(ObjectManager::class);

        $product = new Product();
        $unit = (new ProductUnit())->setCode('item');
        $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

        $priceAttribute = $this->getEntity(PriceAttributePriceList::class, ['id' => 1])
            ->setName('Price Attribute 1')
            ->addCurrencyByCode('USD')
            ->addCurrencyByCode('EUR');

        $priceUSD = $this->getEntity(PriceAttributeProductPrice::class, ['id' => 1])
            ->setUnit($unit)
            ->setPrice(Price::create('100', 'USD'))
            ->setQuantity(1)
            ->setPriceList($priceAttribute)
            ->setProduct($product);

        $priceRepository = $this->createMock(PriceAttributeProductPriceRepository::class);
        $priceRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$priceUSD]);

        $attributeRepository = $this->createMock(PriceAttributePriceListRepository::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$priceAttribute]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $attributeRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceAttributePriceList::class, $attributeRepository],
                [PriceAttributeProductPrice::class, $priceRepository],
            ]);
        $this->registry->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->willReturn($em);

        $form = $this->factory->create(ProductType::class, $product, []);

        // Expect that persist method for new price instance was called on post submit
        $em->expects($this->once())
            ->method('persist')
            ->with(
                (new PriceAttributeProductPrice())
                    ->setUnit($unit)
                    ->setPrice(Price::create('0', 'EUR'))
                    ->setQuantity(1)
                    ->setPriceList($priceAttribute)
                    ->setProduct($product)
            );

        $form->submit([
            PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES => [
                1 => [
                    [ProductAttributePriceType::PRICE => '100'],
                    [ProductAttributePriceType::PRICE => '0'],
                ]
            ]
        ]);
    }

    public function testPostSubmitPricesWithoutValueRemoved()
    {
        $em = $this->createMock(ObjectManager::class);

        $product = new Product();
        $unit = (new ProductUnit())->setCode('item');
        $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

        $priceAttribute = $this->getEntity(PriceAttributePriceList::class, ['id' => 1])
            ->setName('Price Attribute 1')
            ->addCurrencyByCode('EUR')
            ->addCurrencyByCode('USD');

        $priceUSD = $this->getEntity(PriceAttributeProductPrice::class, ['id' => 1])
            ->setUnit($unit)
            ->setPrice(Price::create('100', 'USD'))
            ->setQuantity(1)
            ->setPriceList($priceAttribute)
            ->setProduct($product);

        $priceRepository = $this->createMock(PriceAttributeProductPriceRepository::class);
        $priceRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$priceUSD]);

        $attributeRepository = $this->createMock(PriceAttributePriceListRepository::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$priceAttribute]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $attributeRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceAttributePriceList::class, $attributeRepository],
                [PriceAttributeProductPrice::class, $priceRepository],
            ]);
        $this->registry->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->willReturn($em);

        $form = $this->factory->create(ProductType::class, $product, []);

        // Expect that remove method for nullable price instance was called on post submit
        $em->expects($this->once())
            ->method('remove')
            ->with($priceUSD);
        // For new objects method persist was never called
        $em->expects($this->never())
            ->method('persist');

        $form->submit([
            PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES => [
                1 => [
                    [ProductAttributePriceType::PRICE => ''],
                    [ProductAttributePriceType::PRICE => ''],
                ]
            ]
        ]);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ProductType::class], PriceAttributesProductFormExtension::getExtendedTypes());
    }
}
