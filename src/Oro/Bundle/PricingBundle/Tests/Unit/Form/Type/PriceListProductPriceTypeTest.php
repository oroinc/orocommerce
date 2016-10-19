<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class PriceListProductPriceTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    /**
     * @var PriceListProductPriceType
     */
    protected $formType;

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
        $this->formType = new PriceListProductPriceType();
        $this->formType->setDataClass('Oro\Bundle\PricingBundle\Entity\ProductPrice');

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
                1 => $this->getProductEntityWithPrecision(1, 'kg', 3),
                2 => $this->getProductEntityWithPrecision(2, 'kg', 3)
            ]
        );

        $productUnitSelection = new EntityType(
            $this->prepareProductUnitSelectionChoices(),
            ProductUnitSelectionType::NAME
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|CurrencyConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('getCurrencyList')
            ->will($this->returnValue(['USD', 'EUR']));

        /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper */
        $currencyNameHelper = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper')
            ->disableOriginalConstructor()
            ->getMock();


        $productSelect = new ProductSelectTypeStub();

        $priceType = PriceTypeGenerator::createPriceType();

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    ProductSelectType::NAME => $productSelect,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    PriceType::NAME => $priceType,
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $configManager,
                        $localeSettings,
                        $currencyNameHelper
                    ),
                    QuantityTypeTrait::$name => $this->getQuantityType()
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
     * @param boolean $rounding
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        array $submittedData,
        ProductPrice $expectedData,
        $rounding = false
    ) {
        if ($rounding) {
            $this->addRoundingServiceExpect();
        }

        $form = $this->factory->create($this->formType, $defaultData, []);

        // unit placeholder must not be available for specific entity
        $unitPlaceholder = $form->get('unit')->getConfig()->getOption('placeholder');
        $defaultData->getId() ? $this->assertNull($unitPlaceholder) : $this->assertNotNull($unitPlaceholder);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['USD', 'GBP']);

        /** @var Product $expectedProduct */
        $expectedProduct = $this->getProductEntityWithPrecision(2, 'kg', 3);
        $expectedPrice1 = (new Price())->setValue(42)->setCurrency('USD');
        $expectedPrice2 = (new Price())->setValue(42)->setCurrency('GBP');

        $expectedProductPrice = new ProductPrice();
        $expectedProductPrice
            ->setProduct($expectedProduct)
            ->setQuantity(123)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setPrice($expectedPrice1)
            ->setPriceList($priceList);

        $expectedProductPrice2 = clone $expectedProductPrice;
        $expectedProductPrice2
            ->setQuantity(123.556)
            ->setPrice($expectedPrice2);

        $defaultProductPrice = new ProductPrice();
        $defaultProductPrice->setPriceList($priceList);

        $defaultProductPriceWithId = $this->getEntity('Oro\Bundle\PricingBundle\Entity\ProductPrice', 1);
        $defaultProductPriceWithId->setPriceList($priceList);
        $defaultProductPriceWithId->setPrice((new Price())->setCurrency('USD')->setValue(1));

        return [
            'product price without data' => [
                'defaultData'   => $defaultProductPriceWithId,
                'submittedData' => [
                    'product'  => null,
                    'quantity'  => null,
                    'unit'  => null,
                    'price'  => [
                        'value'    => $defaultProductPriceWithId->getPrice()->getValue(),
                        'currency' => $defaultProductPriceWithId->getPrice()->getCurrency()
                    ],
                ],
                'expectedData'  => clone $defaultProductPriceWithId,
                'rounding'      => false
            ],
            'product price with data' => [
                'defaultData'   => clone $defaultProductPrice,
                'submittedData' => [
                    'product' => 2,
                    'quantity'  => 123,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'USD'
                    ]
                ],
                'expectedData' => $expectedProductPrice,
                'rounding'      => true
            ],
            'product price with data for rounding' => [
                'defaultData'   => clone $defaultProductPrice,
                'submittedData' => [
                    'product' => 2,
                    'quantity'  => 123.5555,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'GBP'
                    ]
                ],
                'expectedData' => $expectedProductPrice2,
                'rounding'     => true
            ]
        ];
    }

    public function testSubmitPriceWithInvalidCurrency()
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['USD', 'UAH']);
        $defaultProductPrice = new ProductPrice();
        $defaultProductPrice->setPriceList($priceList);
        $submittedData = [
            'product' => 2,
            'quantity'  => 123.5555,
            'unit'      => 'kg',
            'price'     => [
                'value'    => 42,
                'currency' => 'CAD'
            ]
        ];
        $form = $this->factory->create($this->formType, $defaultProductPrice, []);
        $form->submit($submittedData);

        $errors = $form->getErrors(true, true);
        $this->assertCount(1, $errors);
        $this->assertFalse($form->isValid());
        $error = $errors->current();
        $this->assertEquals('This value is not valid.', $error->getMessage());
        $this->assertEquals(['{{ value }}' => 'CAD'], $error->getMessageParameters());
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(PriceListProductPriceType::NAME, $this->formType->getName());
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

    /**
     * @param integer $productId
     * @param string $unitCode
     * @param integer $precision
     * @return Product
     */
    protected function getProductEntityWithPrecision($productId, $unitCode, $precision = 0)
    {
        /** @var \Oro\Bundle\ProductBundle\Entity\Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', $productId);

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }
}
