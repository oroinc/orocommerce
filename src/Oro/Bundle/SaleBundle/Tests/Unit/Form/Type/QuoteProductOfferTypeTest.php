<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class QuoteProductOfferTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    private QuoteProductOfferType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->configureQuoteProductOfferFormatter();
        $this->formType = new QuoteProductOfferType();
        parent::setUp();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);
                $this->assertArrayHasKey('allow_prices_override', $options);
                $this->assertArrayHasKey('checksum', $options);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider postSetDataProvider
     */
    public function testPostSetData(QuoteProductOffer $inputData, array $expectedData = []): void
    {
        $form = $this->createForm($inputData, []);

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $form->get($key)->getData(), $key);
        }
    }

    public function testSubmitWithKitProduct()
    {
        $quoteProduct = (new QuoteProduct())->setProduct((new ProductStub())->setType('kit'));
        $quoteProductOffer = (new QuoteProductOffer())->setQuoteProduct($quoteProduct);
        $quoteProductOffer->setChecksum('checksum');

        $form = $this->createForm($quoteProductOffer, []);

        $form->submit([]);
        $view = $form->createView();

        self::assertEquals('checksum', $view->vars['checksum']);
        self::assertTrue($view->vars['allow_prices_override']);
    }

    public function postSetDataProvider(): array
    {
        return [
            'empty values' => [
                'input' => new QuoteProductOffer(),
                'expected' => [
                    'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                    'quantity' => 1,
                ],
            ],
            'existing values' => [
                'input' => (new QuoteProductOffer())
                    ->setPriceType(QuoteProductOffer::PRICE_TYPE_BUNDLED)
                    ->setQuantity(10)
                    ->setAllowIncrements(false),
                'expected' => [
                    'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                    'quantity' => 10,
                ],
            ],
        ];
    }

    #[\Override]
    public function submitProvider(): array
    {
        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getQuoteProductOffer(1, 1),
                'defaultData'   => $this->getQuoteProductOffer(1),
            ],
            'empty quote product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(2, 88, 'kg', self::QPO_PRICE_TYPE1, Price::create(99, 'EUR'))
                    ->setQuoteProduct(null),
                'defaultData'   => $this->getQuoteProductOffer(2)
                    ->setQuoteProduct(null),
            ],
            'empty quantity' => [
                'isValid'       => true,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 11,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(3, 1, 'kg', self::QPO_PRICE_TYPE1, Price::create(11, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(3),
            ],
            'empty price type' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductOffer(4, 88, 'kg', null, Price::create(99, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(4),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 22,
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 33,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(5, 22, null, self::QPO_PRICE_TYPE1, Price::create(33, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(5),
            ],
            'empty price' => [
                'isValid'       => true, //Quote can be create with empty price
                'submittedData' => [
                    'quantity'      => 44,
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                ],
                'expectedData'  => $this->getQuoteProductOffer(6, 44, 'kg', self::QPO_PRICE_TYPE1),
                'defaultData'   => $this->getQuoteProductOffer(6),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 11,
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 22,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(7, 11, 'kg', self::QPO_PRICE_TYPE1, Price::create(22, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(7),
            ],
        ];
    }

    #[\Override]
    protected function createForm(mixed $data, array $options): FormInterface
    {
        return $this->factory->create(QuoteProductOfferType::class, $data, $options);
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $this->getPriceType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    ProductUnitSelectionType::class => $this->getProductUnitSelectionType(),
                    $this->getQuantityType()
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testOnPreSetData(): void
    {
        $form = $this->createForm(new QuoteProductOffer(), []);

        $priceFieldOptions = $form->get('price')->getConfig()->getOptions();
        self::assertNull($priceFieldOptions['currency_empty_value']);
        self::assertFalse($priceFieldOptions['error_bubbling']);
        self::assertTrue($priceFieldOptions['required']);
        self::assertEquals('oro.sale.quoteproductoffer.price.label', $priceFieldOptions['label']);
        self::assertEquals([PriceType::OPTIONAL_VALIDATION_GROUP], $priceFieldOptions['validation_groups']);
        self::assertFalse($priceFieldOptions['match_price_on_null']);
    }

    public function testOnPreSetDataNoEntity(): void
    {
        $form = $this->createForm(null, []);

        $priceFieldOptions = $form->get('price')->getConfig()->getOptions();
        self::assertNull($priceFieldOptions['currency_empty_value']);
        self::assertFalse($priceFieldOptions['error_bubbling']);
        self::assertTrue($priceFieldOptions['required']);
        self::assertEquals('oro.sale.quoteproductoffer.price.label', $priceFieldOptions['label']);
        self::assertEquals([PriceType::OPTIONAL_VALIDATION_GROUP], $priceFieldOptions['validation_groups']);
        self::assertTrue($priceFieldOptions['match_price_on_null']);
    }
}
