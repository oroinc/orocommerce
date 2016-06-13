<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\ProductTypeStub;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductAttributePriceType;

class PriceAttributesProductFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMock(RegistryInterface::class);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $extensions = [
            new PreloadedExtension(
                [
                    ProductType::NAME => new ProductTypeStub(),
                    ProductAttributePriceCollectionType::NAME => new ProductAttributePriceCollectionType(),
                    ProductAttributePriceType::NAME => new ProductAttributePriceType()
                ],
                [
                    ProductType::NAME => [
                        new PriceAttributesProductFormExtension($this->registry)
                    ]
                ]
            )
        ];

        return $extensions;
    }

    public function testSubmit()
    {

        $em = $this->getMock(ObjectManager::class);

        $priceRepository = $this->getMock(ObjectRepository::class);
        $priceRepository->expects($this->once())->method('findBy')->willReturn([]);

        $attributeRepository = $this->getMock(ObjectRepository::class);
        $attributeRepository->expects($this->once())->method('findAll')->willReturn([]);

        $em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            ['OroB2BPricingBundle:PriceAttributePriceList', $attributeRepository],
            ['OroB2BPricingBundle:PriceAttributeProductPrice', $priceRepository],
        ]);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($em);

        $form = $this->factory->create(ProductType::NAME, new Product(), []);

        $form->submit([]);
        $this->assertTrue($form->isValid());
    }

    public function testDataAddedOnPostSetData()
    {
        $em = $this->getMock(ObjectManager::class);

        $product = new Product();
        $unit1 = (new ProductUnit())->setCode('item');
        $unit2 = (new ProductUnit())->setCode('set');
        $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit1))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit2));

        $priceAttribute1 = $this->getEntity(PriceAttributePriceList::class, ['id' => 1])
            ->setName('Price Attribute 1')
            ->addCurrencyByCode('USD')
            ->addCurrencyByCode('EUR');
        $priceAttribute2 = $this->getEntity(PriceAttributePriceList::class, ['id' => 2])
            ->setName('Price Attribute 2')
            ->addCurrencyByCode('USD');

        $priceRepository = $this->getMock(ObjectRepository::class);
        $price1USD = (new PriceAttributeProductPrice())->setUnit($unit1)
            ->setPrice(Price::create('100', 'USD'))
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
        $priceRepository->expects($this->once())->method('findBy')->willReturn([$price1USD, $price1EUR, $price2USD]);

        $attributeRepository = $this->getMock(ObjectRepository::class);
        $attributeRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$priceAttribute1, $priceAttribute2]);

        $em->expects($this->exactly(2))->method('getRepository')->willReturnMap([
            ['OroB2BPricingBundle:PriceAttributePriceList', $attributeRepository],
            ['OroB2BPricingBundle:PriceAttributeProductPrice', $priceRepository],
        ]);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($em);

        $form = $this->factory->create(ProductType::NAME, $product, []);
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
}
