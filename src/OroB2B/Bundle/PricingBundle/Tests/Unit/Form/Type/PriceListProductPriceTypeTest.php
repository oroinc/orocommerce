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

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class PriceListProductPriceTypeTest extends FormIntegrationTestCase
{
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
        $this->formType->setDataClass('OroB2B\Bundle\PricingBundle\Entity\ProductPrice');

        parent::setUp();
    }

    /**
     * @inheritdoc
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
                $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1),
                $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2)
            ]
        );

        $productUnitSelection = new EntityType(
            $this->prepareProductUnitSelectionChoices(),
            ProductUnitSelectionType::NAME
        );

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->will($this->returnValue(['USD', 'EUR']));

        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $productSelect = new ProductSelectTypeStub();

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    ProductSelectType::NAME => $productSelect,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    PriceType::NAME => new PriceType(),
                    CurrencySelectionType::NAME => new CurrencySelectionType($configManager, $localeSettings)
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
        array $submittedData,
        ProductPrice $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertEquals([], $form->getErrors());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        /** @var Product $expectedProduct */
        $expectedProduct = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2);
        $expectedUnit = (new ProductUnit())->setCode('kg');
        $expectedPrice = (new Price())->setValue(42)->setCurrency('USD');

        $expectedProductPrice = new ProductPrice();
        $expectedProductPrice
            ->setProduct($expectedProduct)
            ->setQuantity(123)
            ->setUnit($expectedUnit)
            ->setPrice($expectedPrice);

        $defaultProductPrice = new ProductPrice();

        return [
            'product price without data' => [
                'defaultData'   => $defaultProductPrice,
                'submittedData' => [],
                'expectedData'  => $defaultProductPrice
            ],
            'product price with data' => [
                'defaultData'   => $defaultProductPrice,
                'submittedData' => [
                    'product' => 1,
                    'quantity'  => 123,
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => 42,
                        'currency' => 'USD'
                    ]
                ],
                'expectedData' => $expectedProductPrice
            ]

        ];
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
}
