<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

class QuoteFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_SKU = 'test-sku';
    const PRICE1 = 100;
    const PRICE2 = 200;
    const CURRENCY = 'USD';
    const QUANTITY = 10;
    const UNIT1 = 'kg';
    const UNIT2 = 'set';
    const TIER_PRICES = [
        1 => [
            [
                'quantity' => 1,
                'unit' => self::UNIT2,
                'currency' => self::CURRENCY,
                'price' => self::PRICE2,
            ],
            [
                'quantity' => 20,
                'unit' => self::UNIT2,
                'currency' => self::CURRENCY,
                'price' => self::PRICE2,
            ],
        ],
    ];

    /** @var QuoteProductPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var QuoteFormSubscriber */
    private $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this->createMock(QuoteProductPriceProvider::class);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())->method('trans')->willReturnArgument(0);

        $this->subscriber = new QuoteFormSubscriber($this->provider, $translator);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals([FormEvents::PRE_SUBMIT => 'onPreSubmit'], QuoteFormSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider onPreSubmitProvider
     *
     * @param array $data
     * @param array $options
     * @param bool $expectError
     * @param bool $expectPriceChange
     */
    public function testOnPreSubmit(array $data, array $options = [], $expectError = false, $expectPriceChange = false)
    {
        $quote = $this->getQuote(42);
        $quote->expects($this->exactly((int) $expectPriceChange))->method('setPricesChanged')->with(true);

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())->method('getOptions')->willReturn($options);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($quote);
        $form->expects($this->once())->method('getConfig')->willReturn($config);
        $form->expects($this->exactly($expectError ? 1 : 0))->method('addError')->with(new FormError($expectError));

        $this->provider->expects($this->any())->method('getTierPricesForProducts')->willReturn(self::TIER_PRICES);

        $this->subscriber->onPreSubmit(new FormEvent($form, $data));
    }

    /**
     * @return array
     */
    public function onPreSubmitProvider()
    {
        return [
            'no products' => [
                'data' => ['quoteProducts' => []],
                'options' => [],
                'expectError' => '',
                'expectPriceChange' => false,
            ],
            'no changes' => [
                'data' => $this->getData(),
                'options' => [],
                'expectError' => '',
                'expectPriceChange' => false,
            ],
            'price changed' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => true],
                'expectError' => '',
                'expectPriceChange' => true,
            ],
            'price changed not allow override' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => false, 'allow_add_free_form_items' => true],
                'expectError' => 'oro.sale.quote.form.error.price_override',
                'expectPriceChange' => true,
            ],
            'price changed not allow free form' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => false],
                'expectError' => '',
                'expectPriceChange' => true,
            ],
            'price changed tier price' => [
                'data' => $this->getData(self::PRICE2, self::CURRENCY, 5, self::UNIT2),
                'options' => ['allow_prices_override' => false, 'allow_add_free_form_items' => false],
                'expectError' => '',
                'expectPriceChange' => true,
            ],
            'product free form allowed' => [
                'data' => $this->getData(
                    self::PRICE2,
                    self::CURRENCY,
                    self::QUANTITY,
                    self::UNIT1,
                    self::PRODUCT_SKU,
                    true
                ),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => true],
                'expectError' => false,
                'expectPriceChange' => true,
            ],
            'product free form not allowed' => [
                'data' => $this->getData(
                    self::PRICE2,
                    self::CURRENCY,
                    self::QUANTITY,
                    self::UNIT1,
                    self::PRODUCT_SKU,
                    true
                ),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => false],
                'expectError' => 'oro.sale.quote.form.error.free_form_price_override',
                'expectPriceChange' => true,
            ],
        ];
    }

    /**
     * @dataProvider onPreSubmitSkipProvider
     *
     * @param Quote|null $quote
     * @param array $data
     */
    public function testOnPreSubmitSkip($quote, array $data)
    {
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())->method('getOptions')->willReturn([]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($quote);
        $form->expects($this->any())->method('getConfig')->willReturn($config);
        $form->expects($this->never())->method('addError');

        $this->provider->expects($this->never())->method('getTierPricesForProducts');

        $this->subscriber->onPreSubmit(new FormEvent($form, $data));
    }

    /**
     * @return array
     */
    public function onPreSubmitSkipProvider()
    {
        return [
            'no quote' => [
                'quote' => null,
                'data' => $this->getData(self::PRICE2)
            ],
            'no quote id' => [
                'quote' => $this->getQuote(null),
                'data' => $this->getData(self::PRICE2)
            ],
            'no data' => [
                'quote' => $this->getQuote(42),
                'data' => []
            ],
        ];
    }

    /**
     * @param int $id
     *
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuote($id)
    {
        $quote = $this->createMock(Quote::class);
        $quoteProduct = $this->createMock(QuoteProduct::class);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);

        $price = $this->createMock(Price::class);
        $price->expects($this->any())->method('getCurrency')->willReturn(self::CURRENCY);
        $price->expects($this->any())->method('getValue')->willReturn(self::PRICE1);

        $quoteProductOffer->expects($this->any())->method('getPrice')->willReturn($price);
        $quoteProductOffer->expects($this->any())->method('getProductUnitCode')->willReturn(self::UNIT1);
        $quoteProductOffer->expects($this->any())->method('getQuantity')->willReturn(self::QUANTITY);

        $quote->expects($this->any())->method('getId')->willReturn($id);
        $quote->expects($this->any())->method('getQuoteProducts')->willReturn([$quoteProduct]);

        $quoteProduct->expects($this->any())->method('getQuoteProductOffers')->willReturn([$quoteProductOffer]);
        $quoteProduct->expects($this->any())->method('getProductSku')->willReturn(self::PRODUCT_SKU);

        return $quote;
    }

    /**
     * @param int $price
     * @param string $currency
     * @param int $quantity
     * @param string $unit
     * @param string $sku
     * @param bool $isFreeForm
     * @return array
     */
    private function getData(
        $price = self::PRICE1,
        $currency = self::CURRENCY,
        $quantity = self::QUANTITY,
        $unit = self::UNIT1,
        $sku = self::PRODUCT_SKU,
        $isFreeForm = false
    ) {
        return [
            'quoteProducts' => [
                [
                    'productSku' => $sku,
                    'product' => $isFreeForm ? null : 1,
                    'quoteProductOffers' => [
                        [
                            'quantity' => $quantity,
                            'productUnit' => $unit,
                            'price' => ['currency' => $currency, 'value' => $price],
                        ]
                    ]
                ]
            ]
        ];
    }
}
