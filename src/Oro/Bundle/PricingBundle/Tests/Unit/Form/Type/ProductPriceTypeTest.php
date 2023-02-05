<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
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
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class ProductPriceTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    private ProductPriceType $formType;

    private array $priceLists = ['Test', 'Test 01'];
    private array $units = ['item', 'kg' ];
    private string $locale;

    protected function setUp(): void
    {
        $this->locale = \Locale::getDefault();
        $this->formType = new ProductPriceType();
        $this->formType->setDataClass(ProductPrice::class);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->locale);
        parent::tearDown();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    new EntityTypeStub([1 => $this->getPriceList(1), 2 => $this->getPriceList(2)]),
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    ProductPriceUnitSelectorType::class => new ProductUnitSelectionTypeStub(
                        $this->prepareProductUnitSelectionChoices()
                    ),
                    $priceType,
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    $this->getQuantityType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        array $submittedData,
        ProductPrice $expectedData,
        string $locale = 'en'
    ) {
        \Locale::setDefault($locale);
        $form = $this->factory->create(ProductPriceType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        $product = new Product();
        $product->setSku('sku_test_001');
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit((new ProductUnit())->setCode('kg'));
        $productUnitPrecision->setPrecision(5);
        $existingUnit = (new ProductUnit())->setCode('kg');
        $existingPrice = (new Price())->setValue('42.0000')->setCurrency('USD');

        $product->addUnitPrecision($productUnitPrecision);

        $existingProductPriceList = $this->getPriceList(1);
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

        $expectedPriceList = $this->getPriceList(2);
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

    public function testGetName()
    {
        $this->assertEquals(ProductPriceType::NAME, $this->formType->getName());
    }

    private function prepareProductUnitSelectionChoices(): array
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }

    public function testOnPreSetData()
    {
        $existingProductPriceList = $this->getPriceList(1);
        $existingProductPriceList->addCurrencyByCode('NewUSD');
        $existingProductPrice = new ProductPrice();

        $existingProductPrice->setPriceList($existingProductPriceList);

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
