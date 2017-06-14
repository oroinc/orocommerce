<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber;

class QuoteFormSubscriberTest extends \PHPUnit_Framework_TestCase
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

    /** @var ProductPriceProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productPriceProvider;

    /** @var PriceListTreeHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $treeHandler;

    /** @var QuoteFormSubscriber */
    private $subscriber;

    protected function setUp()
    {
        parent::setUp();
        $this->productPriceProvider = $this->createMock(ProductPriceProvider::class);
        $this->treeHandler = $this->createMock(PriceListTreeHandler::class);
        $priceList = $this->createMock(BasePriceList::class);
        $this->treeHandler->expects($this->any())->method('getPriceList')->willReturn($priceList);
        $this->productPriceProvider->expects($this->any())
            ->method('getPriceByPriceListIdAndProductIds')
            ->willReturn(self::TIER_PRICES);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())->method('trans')->willReturnArgument(0);

        $this->subscriber = new QuoteFormSubscriber(
            $this->productPriceProvider,
            $this->treeHandler,
            $translator
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals([FormEvents::PRE_SUBMIT => 'onPreSubmit'], QuoteFormSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider onPreSubmitProvider
     * @param array $data
     * @param array $options
     * @param bool $expectError
     * @param bool $expectPriceChange
     */
    public function testOnPreSubmit(array $data, array $options = [], $expectError = false, $expectPriceChange = false)
    {
        $quote = $this->getQuote();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($quote);
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())->method('getOptions')->willReturn($options);

        $form->expects($this->once())->method('getConfig')->willReturn($config);

        $form->expects($this->exactly((int) $expectError))->method('addError')->with(
            new FormError('oro.sale.quote.form.error.price_override')
        );
        $quote->expects($this->exactly((int) $expectPriceChange))->method('setPricesChanged')->with(true);
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
                'expectError' => false,
                'expectPriceChange' => false,
            ],
            'no changes' => [
                'data' => $this->getData(),
                'options' => [],
                'expectError' => false,
                'expectPriceChange' => false,
            ],
            'price changed' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => true],
                'expectError' => false,
                'expectPriceChange' => true,
            ],
            'price changed not allow override' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => false, 'allow_add_free_form_items' => true],
                'expectError' => true,
                'expectPriceChange' => true,
            ],
            'price changed not allow free form' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => false],
                'expectError' => true,
                'expectPriceChange' => true,
            ],
            'price changed tier price' => [
                'data' => $this->getData(self::PRICE2, self::CURRENCY, 5, self::UNIT2),
                'options' => ['allow_prices_override' => false, 'allow_add_free_form_items' => false],
                'expectError' => false,
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
                'expectError' => true,
                'expectPriceChange' => true,
            ],
        ];
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuote()
    {
        $quote = $this->createMock(Quote::class);
        $quoteProduct = $this->createMock(QuoteProduct::class);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);

        $price = $this->createMock(Price::class);

        $price->expects($this->once())->method('getCurrency')->willReturn(self::CURRENCY);
        $price->expects($this->once())->method('getValue')->willReturn(self::PRICE1);
        $quoteProductOffer->expects($this->any())->method('getPrice')->willReturn($price);
        $quoteProductOffer->expects($this->any())->method('getProductUnitCode')->willReturn(self::UNIT1);
        $quoteProductOffer->expects($this->any())->method('getQuantity')->willReturn(self::QUANTITY);

        $quote->expects($this->once())->method('getQuoteProducts')->willReturn([$quoteProduct]);
        $quoteProduct->expects($this->once())->method('getQuoteProductOffers')->willReturn([$quoteProductOffer]);
        $quoteProduct->expects($this->once())->method('getProductSku')->willReturn(self::PRODUCT_SKU);

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
