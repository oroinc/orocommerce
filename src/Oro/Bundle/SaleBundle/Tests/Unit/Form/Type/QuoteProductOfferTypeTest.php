<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteProductOfferTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var QuoteProductOfferType */
    protected $formType;

    protected function setUp(): void
    {
        $this->configureQuoteProductOfferFormatter();
        $this->formType = new QuoteProductOfferType($this->quoteProductOfferFormatter);
        $this->formType->setDataClass(QuoteProductOffer::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);

                return true;
            }))
        ;

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider postSetDataProvider
     */
    public function testPostSetData(QuoteProductOffer $inputData, array $expectedData = [])
    {
        $form = $this->factory->create(QuoteProductOfferType::class, $inputData);

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $form->get($key)->getData(), $key);
        }
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $this->preparePriceType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    ProductUnitSelectionType::class => $this->prepareProductUnitSelectionType(),
                    $this->getQuantityType()
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testOnPreSetData()
    {
        $formMock = $this->createMock(FormInterface::class);
        $event = new FormEvent($formMock, new QuoteProductOffer());

        $formMock->expects($this->once())
            ->method('add')
            ->with(
                'price',
                PriceType::class,
                [
                    'currency_empty_value' => null,
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'oro.sale.quoteproductoffer.price.label',
                    //Price value may be not set by user while creating quote
                    'validation_groups' => [PriceType::OPTIONAL_VALIDATION_GROUP],
                    'match_price_on_null' => false
                ]
            );

        $this->formType->onPreSetData($event);
    }

    public function testOnPreSetDataNoEntity()
    {
        $formMock = $this->createMock(FormInterface::class);
        $event = new FormEvent($formMock, null);

        $formMock->expects($this->never())->method('add');

        $this->formType->onPreSetData($event);
    }
}
