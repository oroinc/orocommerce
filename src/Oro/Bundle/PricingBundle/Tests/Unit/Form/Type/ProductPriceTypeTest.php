<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceUnitSelectorType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

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
     * @var string
     */
    protected $locale;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->locale = \Locale::getDefault();
        $this->formType = new ProductPriceType();
        $this->formType->setDataClass('Oro\Bundle\PricingBundle\Entity\ProductPrice');
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        \Locale::setDefault($this->locale);
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 1),
                2 => $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 2)
            ]
        );

        $productUnitSelection = new ProductUnitSelectionTypeStub(
            $this->prepareProductUnitSelectionChoices(),
            ProductPriceUnitSelectorType::NAME
        );
        $priceType = PriceTypeGenerator::createPriceType($this);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $entityType->getName() => $entityType,
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    ProductPriceUnitSelectorType::class => $productUnitSelection,
                    PriceType::class => $priceType,
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
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
     * @param string $locale
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        $submittedData,
        ProductPrice $expectedData,
        $locale = 'en'
    ) {
        \Locale::setDefault($locale);
        $form = $this->factory->create(ProductPriceType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
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
        $existingPrice = (new Price())->setValue('42.0000')->setCurrency('USD');

        $product->addUnitPrecision($productUnitPrecision);

        /** @var PriceList $existingProductPriceList */
        $existingProductPriceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 1);
        $existingProductPrice = new ProductPrice();
        $rule = new PriceRule();
        $existingProductPrice
            ->setProduct($product)
            ->setPriceList($existingProductPriceList)
            ->setQuantity('123')
            ->setUnit($existingUnit)
            ->setPrice($existingPrice)
            ->setPriceRule($rule);

        $generatedPrice = new ProductPrice();
        $generatedPrice
            ->setProduct($product)
            ->setPriceList($existingProductPriceList)
            ->setQuantity('123')
            ->setUnit((new ProductUnit())->setCode('kg'))
            ->setPrice((new Price())->setValue('42.0000')->setCurrency('USD'))
            ->setPriceRule(new PriceRule());

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 2);
        $expectedUnit = (new ProductUnit())->setCode('item');
        $expectedPrice = (new Price())->setValue('43.0000')->setCurrency('EUR');

        $expectedProductPrice = new ProductPrice();
        $expectedProductPrice
            ->setPriceList($expectedPriceList)
            ->setQuantity('124')
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
                    'quantity'  => '124',
                    'unit'      => 'item',
                    'price'     => [
                        'value'    => '43.0000',
                        'currency' => 'EUR'
                    ]
                ],
                'expectedData' => $expectedProductPrice,
                'locale' => 'en'
            ],
            'product price with precision' => [
                'defaultData'   => $existingProductPrice,
                'submittedData' => [
                    'priceList' => 2,
                    'quantity'  => '124',
                    'unit'      => 'item',
                    'price'     => [
                        'value'    => '43.0000',
                        'currency' => 'EUR'
                    ]
                ],
                'expectedData' => $updatedExpectedProductPrice,
                'locale' => 'en'
            ],
            'product price without changes' => [
                'defaultData'   => $generatedPrice,
                'submittedData' => [
                    'priceList' => 1,
                    'quantity'  => '123',
                    'unit'      => 'kg',
                    'price'     => [
                        'value'    => '42,0000',
                        'currency' => 'USD'
                    ],
                ],
                'expectedData' => clone $generatedPrice,
                'locale' => 'de'
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

    public function testOnPreSetData()
    {
        /** @var PriceList $existingProductPriceList */
        $existingProductPriceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 1);
        $existingProductPriceList->addCurrencyByCode('NewUSD');
        $existingProductPrice = new ProductPrice();

        $existingProductPrice->setPriceList($existingProductPriceList);

        /**
         * @var $formMock FormInterface|\PHPUnit\Framework\MockObject\MockObject
         */
        $formMock = $this->createMock(FormInterface::class);

        $event = new FormEvent($formMock, $existingProductPrice);

        $formMock->expects($this->once())
            ->method('add')
            ->with(
                'price',
                PriceType::class,
                [
                    'label' => 'oro.pricing.price.label',
                    'currency_empty_value' => 'oro.pricing.pricelist.form.pricelist_required',
                    'currencies_list' => ['NewUSD'],
                    'full_currency_list' => false
                ]
            );

        $this->formType->onPreSetData($event);
    }

    public function testOnPreSetDataNoPrice()
    {
        /**
         * @var $formMock FormInterface|\PHPUnit\Framework\MockObject\MockObject
         */
        $formMock = $this->createMock(FormInterface::class);
        $event = new FormEvent($formMock, null);

        $formMock->expects($this->once())
            ->method('add')
            ->with(
                'price',
                PriceType::class,
                [
                    'label' => 'oro.pricing.price.label',
                    'currency_empty_value' => 'oro.pricing.pricelist.form.pricelist_required',
                    'currencies_list' => null,
                    'full_currency_list' => true
                ]
            );

        $this->formType->onPreSetData($event);
    }

    public function testOnPreSetDataNoPriceList()
    {
        $existingProductPrice = new ProductPrice();

        /**
         * @var $formMock FormInterface|\PHPUnit\Framework\MockObject\MockObject
         */
        $formMock = $this->createMock(FormInterface::class);
        $event = new FormEvent($formMock, $existingProductPrice);

        $formMock->expects($this->once())
            ->method('add')
            ->with(
                'price',
                PriceType::class,
                [
                    'label' => 'oro.pricing.price.label',
                    'currency_empty_value' => 'oro.pricing.pricelist.form.pricelist_required',
                    'currencies_list' => null,
                    'full_currency_list' => true
                ]
            );

        $this->formType->onPreSetData($event);
    }
}
