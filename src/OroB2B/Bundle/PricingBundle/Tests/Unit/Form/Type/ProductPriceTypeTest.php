<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceUnitSelectorType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;

class ProductPriceTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

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

        $productUnitSelection = new ProductUnitSelectionTypeStub(
            $this->prepareProductUnitSelectionChoices(),
            ProductPriceUnitSelectorType::NAME
        );
        $priceType = PriceTypeGenerator::createPriceType();

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                    ProductPriceUnitSelectorType::NAME => $productUnitSelection,
                    PriceType::NAME => $priceType,
                    CurrencySelectionType::NAME => new CurrencySelectionTypeStub(),
                    QuantityTypeTrait::$name => $this->getQuantityType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param ProductPrice $defaultData
     * @param array $submittedData
     * @param ProductPrice $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        $submittedData,
        ProductPrice $expectedData
    ) {
        $this->addRoundingServiceExpect();
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

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
        $rule = new PriceRule();
        $existingProductPrice
            ->setProduct($product)
            ->setPriceList($existingProductPriceList)
            ->setQuantity(123)
            ->setUnit($existingUnit)
            ->setPrice($existingPrice)
            ->setPriceRule($rule);

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2);
        $expectedUnit = (new ProductUnit())->setCode('item');
        $expectedPrice = (new Price())->setValue(43)->setCurrency('EUR');

        $expectedProductPrice = new ProductPrice();
        $expectedProductPrice
            ->setPriceList($expectedPriceList)
            ->setQuantity(124)
            ->setUnit($expectedUnit)
            ->setPrice($expectedPrice)
            ->setProduct($product)
            ->setPriceRule(null);

        $updatedExpectedProductPrice = clone $expectedProductPrice;
        $updatedExpectedProductPrice->setProduct($product);

        return [
            'product price with data' => [
                'defaultData'   => (new ProductPrice())->setProduct($product)->setUnit($existingUnit),
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
            ],
            'product price without changes' => [
                'defaultData' => $existingProductPrice,
                'submittedData' => [
                    'priceList' => $existingProductPrice->getPriceList()->getId(),
                    'quantity' => $existingProductPrice->getQuantity(),
                    'unit' => $existingProductPrice->getUnit()->getCode(),
                    'price' => [
                        'value' => $existingProductPrice->getPrice()->getValue(),
                        'currency' => $existingProductPrice->getPrice()->getCurrency(),
                    ],
                ],
                'expectedData' => $existingProductPrice,
            ],
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
