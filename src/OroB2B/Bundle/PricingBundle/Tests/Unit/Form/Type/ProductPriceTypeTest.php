<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductPriceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductPriceType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $priceLists = [
        'Test',
        'Test 01'
    ];

    /**
     * @var array
     */
    protected $units = [
        'item',
        'kg'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new ProductPriceType();
        $this->formType->setDataClass('OroB2B\Bundle\PricingBundle\Entity\ProductPrice');
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 1),
                2 => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2)
            ]
        );

        $productUnitSelection = new EntityType(
            $this->prepareProductUnitSelectionChoices(),
            ProductUnitSelectionType::NAME
        );

        $priceType = new PriceType();
        $priceType->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    PriceType::NAME => $priceType,
                    CurrencySelectionType::NAME => new CurrencySelectionTypeStub()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param ProductPrice $defaultData
     * @param $submittedData
     * @param ProductPrice $expectedData
     * @param array $expectedOptions
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        $submittedData,
        ProductPrice $expectedData,
        array $expectedOptions = null
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        if ($expectedOptions) {
            $quantityConfig = $form->get('quantity')->getConfig();
            foreach ($expectedOptions as $key => $value) {
                $this->assertTrue($quantityConfig->hasOption($key));
                $this->assertEquals($value, $quantityConfig->getOption($key));
            }
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $product = new Product();
        $product->setSku('sku_test_001');
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit((new ProductUnit())->setCode('kg'));
        $productUnitPrecision->setPrecision(5);
        $existingUnit = (new ProductUnit())->setCode('kg');
        $existingPrice = (new Price())->setValue(42)->setCurrency('USD');

        $product->addUnitPrecision($productUnitPrecision);

        /** @var PriceList $existingProductPriceList */
        $existingProductPriceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 1);
        $existingProductPrice = new ProductPrice();
        $existingProductPrice
            ->setProduct($product)
            ->setPriceList($existingProductPriceList)
            ->setQuantity(123)
            ->setUnit($existingUnit)
            ->setPrice($existingPrice);

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2);
        $expectedUnit = (new ProductUnit())->setCode('item');
        $expectedPrice = (new Price())->setValue(43)->setCurrency('EUR');

        $expectedProductPrice = new ProductPrice();
        $expectedProductPrice
            ->setPriceList($expectedPriceList)
            ->setQuantity(124)
            ->setUnit($expectedUnit)
            ->setPrice($expectedPrice);

        $updatedExpectedProductPrice = clone($expectedProductPrice);
        $updatedExpectedProductPrice->setProduct($product);

        return [
            'product price with data' => [
                'defaultData'   => new ProductPrice(),
                'submittedData' => [
                    'priceList' => 2,
                    'quantity'  => 124,
                    'unit'      => 'item',
                    'price'     => [
                        'value'    => 43,
                        'currency' => 'EUR'
                    ]
                ],
                'expectedData' => $expectedProductPrice
            ],
            'product price with precision' => [
                'defaultData'   => $existingProductPrice,
                'submittedData' => [
                    'priceList' => 2,
                    'quantity'  => 124,
                    'unit'      => 'item',
                    'price'     => [
                        'value'    => 43,
                        'currency' => 'EUR'
                    ]
                ],
                'expectedData' => $updatedExpectedProductPrice,
                'expectedOptions' => [
                    'precision' => 5
                ]
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(ProductPriceType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    protected function prepareProductUnitSelectionChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);
        return $entity;
    }
}
