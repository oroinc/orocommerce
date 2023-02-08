<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
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
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class PriceListProductPriceTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    /** @var PriceListProductPriceType */
    private $formType;

    private array $units = ['item', 'kg'];

    protected function setUp(): void
    {
        $this->formType = new PriceListProductPriceType();
        $this->formType->setDataClass(ProductPrice::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EntityType::class => new EntityTypeStub([
                        1 => $this->getProductEntityWithPrecision(1, 'kg', 3),
                        2 => $this->getProductEntityWithPrecision(2, 'kg', 3)
                    ]),
                    ProductSelectType::class => new ProductSelectTypeStub(),
                    ProductUnitSelectionType::class => new EntityTypeStub($this->prepareProductUnitSelectionChoices()),
                    $priceType,
                    CurrencySelectionType::class => new CurrencySelectionType(
                        $currencyProvider,
                        $this->createMock(LocaleSettings::class),
                        $this->createMock(CurrencyNameHelper::class)
                    ),
                    $this->getQuantityType()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductPrice $defaultData,
        array $submittedData,
        ProductPrice $expectedData
    ) {
        $form = $this->factory->create(PriceListProductPriceType::class, $defaultData);

        // unit placeholder must not be available for specific entity
        $unitPlaceholder = $form->get('unit')->getConfig()->getOption('placeholder');
        $defaultData->getId() ? $this->assertNull($unitPlaceholder) : $this->assertNotNull($unitPlaceholder);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertCount(0, $form->getErrors(true));
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['USD', 'GBP']);

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
            ->setQuantity(123.5555)
            ->setPrice($expectedPrice2);

        $defaultProductPrice = new ProductPrice();
        $defaultProductPrice->setPriceList($priceList);

        $defaultProductPriceWithId = new ProductPrice();
        ReflectionUtil::setId($defaultProductPriceWithId, 1);
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
                'expectedData'  => $defaultProductPriceWithId,
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
        $form = $this->factory->create(PriceListProductPriceType::class, $defaultProductPrice);
        $form->submit($submittedData);

        $errors = $form->getErrors(true);
        $this->assertCount(1, $errors);
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $error = $errors->current();
        $this->assertEquals('This value is not valid.', $error->getMessage());
        $this->assertEquals(['{{ value }}' => 'CAD'], $error->getMessageParameters());
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

    private function getProductEntityWithPrecision(int $productId, string $unitCode, int $precision = 0): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $productId);

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
