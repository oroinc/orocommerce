<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteFormSubscriberTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const WEBSITE_ID = 2;
    const CUSTOMER_ID = 4;
    const PRODUCT_SKU = 'test-sku';
    const PRODUCT_ID = 137;
    const PRICE1 = 100;
    const PRICE2 = 200;
    const CURRENCY = 'USD';
    const QUANTITY = 10;
    const UNIT1 = 'kg';
    const UNIT2 = 'set';

    /** @var QuoteProductPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var QuoteFormSubscriber */
    private $subscriber;

    private $tierPrices = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->createMock(QuoteProductPriceProvider::class);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())->method('trans')->willReturnArgument(0);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->subscriber = new QuoteFormSubscriber(
            $this->provider,
            $translator,
            $this->doctrineHelper
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SUBMIT => 'onPreSubmit',
                FormEvents::SUBMIT => 'onSubmit',
            ],
            QuoteFormSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider noWebsiteDataProvider
     * @param array $data
     */
    public function testOnPreSubmitWithNoWebsite(?array $data): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($quote);

        $this->subscriber->onPreSubmit(new FormEvent($form, $data));

        $this->assertNull($quote->getWebsite());
    }

    public function noWebsiteDataProvider(): array
    {
        return [
            'no data' => [
                'data' => null
            ],
            'no website' => [
                'data' => []
            ],
            'empty website' => [
                'data' => ['website' => null]
            ],
        ];
    }

    public function testOnPreSubmitWithWebsite(): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($quote);

        $website = new Website();
        $entityRepository = $this->configureRepository(Website::class);
        $entityRepository
            ->expects($this->once())
            ->method('find')
            ->with(self::WEBSITE_ID)
            ->willReturn($website);

        $this->subscriber->onPreSubmit(new FormEvent($form, ['website' => self::WEBSITE_ID]));

        $this->assertSame($website, $quote->getWebsite());
    }

    /**
     * @dataProvider noCustomerDataProvider
     * @param array $data
     */
    public function testOnPreSubmitWithNoCustomer(?array $data): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($quote);

        $this->subscriber->onPreSubmit(new FormEvent($form, $data));

        $this->assertNull($quote->getCustomer());
    }

    public function noCustomerDataProvider(): array
    {
        return [
            'no data' => [
                'data' => null
            ],
            'no customer' => [
                'data' => []
            ],
            'empty customer' => [
                'data' => ['customer' => null]
            ],
        ];
    }

    public function testOnPreSubmitWithCustomer(): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($quote);

        $customer = new Customer();
        $entityRepository = $this->configureRepository(Customer::class);
        $entityRepository
            ->expects($this->once())
            ->method('find')
            ->with(self::CUSTOMER_ID)
            ->willReturn($customer);

        $this->subscriber->onPreSubmit(new FormEvent($form, ['customer' => self::CUSTOMER_ID]));

        $this->assertSame($customer, $quote->getCustomer());
    }

    /**
     * @dataProvider onSubmitProvider
     *
     * @param array $data
     * @param array $options
     * @param bool $expectError
     * @param bool $expectPriceChange
     */
    public function testOnSubmit(array $data, array $options = [], $expectError = false, $expectPriceChange = false)
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, $data);

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())->method('getOptions')->willReturn($options);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getConfig')->willReturn($config);
        $form->expects($this->exactly($expectError ? 1 : 0))->method('addError')->with(new FormError($expectError));

        if (!$data['quoteProducts']) {
            $this->provider->expects($this->never())
                ->method('getMatchedProductPrice');
        } else {
            $price = $this->getEntity(Price::class, ['value' => self::PRICE1]);
            $matchedPrice = $expectPriceChange ? null : $price;
            $this->provider->expects($this->once())
                ->method('getMatchedProductPrice')
                ->willReturn($matchedPrice);
        }

        $this->provider->expects($this->never())
            ->method('getTierPricesForProducts');

        $this->subscriber->onSubmit(new FormEvent($form, $quote));

        $this->assertEquals($expectPriceChange, $quote->isPricesChanged());
    }

    /**
     * @return array
     */
    public function onSubmitProvider()
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
            'price changed not allow free form' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => false],
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
     * @dataProvider onSubmitWithCheckingTierPriceProvider
     *
     * @param array $data
     * @param array $options
     * @param bool $expectError
     * @param bool $expectPriceChange
     */
    public function testOnSubmitWithCheckingTierPrice(
        array $data,
        array $options = [],
        $expectError = false,
        $expectPriceChange = false
    ) {
        $tierPrices = [
            self::PRODUCT_ID => [
                new ProductPriceDTO(
                    $this->getEntity(Product::class, ['id' => self::PRODUCT_ID]),
                    Price::create(self::PRICE2, self::CURRENCY),
                    1,
                    $this->getEntity(ProductUnit::class, ['code' => self::UNIT2])
                ),
                new ProductPriceDTO(
                    $this->getEntity(Product::class, ['id' => self::PRODUCT_ID]),
                    Price::create(self::PRICE2, self::CURRENCY),
                    20,
                    $this->getEntity(ProductUnit::class, ['code' => self::UNIT2])
                )
            ]
        ];

        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, $data);

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())->method('getOptions')->willReturn($options);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getConfig')->willReturn($config);
        $form->expects($this->exactly($expectError ? 1 : 0))->method('addError')->with(new FormError($expectError));

        $product = new ProductStub();
        $product->setId(self::PRODUCT_ID);
        $product->setSku(self::PRODUCT_SKU);

        $this->provider
            ->expects($this->once())
            ->method('getTierPricesForProducts')
            ->with($quote, [$product])
            ->willReturn($tierPrices);

        $this->subscriber->onSubmit(new FormEvent($form, $quote));

        $this->assertEquals($expectPriceChange, $quote->isPricesChanged());
    }

    /**
     * @return array
     */
    public function onSubmitWithCheckingTierPriceProvider()
    {
        return [
            'price changed tier price' => [
                'data' => $this->getData(self::PRICE2, self::CURRENCY, 5, self::UNIT2),
                'options' => ['allow_prices_override' => false, 'allow_add_free_form_items' => false],
                'expectError' => '',
                'expectPriceChange' => true,
            ],
            'price changed not allow override' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => false, 'allow_add_free_form_items' => true],
                'expectError' => 'oro.sale.quote.form.error.price_override',
                'expectPriceChange' => true,
            ]
        ];
    }

    public function testOnSubmitSkip()
    {
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())->method('getOptions')->willReturn([]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())->method('getConfig')->willReturn($config);
        $form->expects($this->never())->method('addError');

        $this->provider->expects($this->never())->method('getTierPricesForProducts');

        $this->subscriber->onSubmit(new FormEvent($form, null));
    }

    public function testOnSubmitNewQuoteWithoutWebsiteAndCustomerData()
    {
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())->method('getOptions')->willReturn([]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())->method('getConfig')->willReturn($config);
        $form->expects($this->never())->method('addError');

        $tierPrices = [
            self::PRODUCT_ID => [
                new ProductPriceDTO(
                    $this->getEntity(Product::class, ['id' => self::PRODUCT_ID]),
                    Price::create(self::PRICE2, self::CURRENCY),
                    1,
                    $this->getEntity(ProductUnit::class, ['code' => self::UNIT2])
                ),
                new ProductPriceDTO(
                    $this->getEntity(Product::class, ['id' => self::PRODUCT_ID]),
                    Price::create(self::PRICE2, self::CURRENCY),
                    20,
                    $this->getEntity(ProductUnit::class, ['code' => self::UNIT2])
                )
            ]
        ];

        $data = $this->getData(self::PRICE2, self::CURRENCY, 5, self::UNIT2);
        $quote = $this->getEntity(Quote::class, $data);

        $product = new ProductStub();
        $product->setId(self::PRODUCT_ID);
        $product->setSku(self::PRODUCT_SKU);

        $this->provider->expects($this->once())
            ->method('getTierPricesForProducts')
            ->with($quote, [$product])
            ->willReturn($tierPrices);

        $this->subscriber->onSubmit(new FormEvent($form, $quote));
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
        if ($isFreeForm) {
            $product = null;
        } else {
            $product = new ProductStub();
            $product->setId(self::PRODUCT_ID);
            $product->setSku($sku);
        }

        return [
            'quoteProducts' => [
                $this->getEntity(QuoteProduct::class, [
                    'productSku' => $sku,
                    'product' => $product,
                    'quoteProductOffers' => [
                        $this->getEntity(QuoteProductOffer::class, [
                            'quantity' => $quantity,
                            'quoteProduct' => $this->getEntity(QuoteProduct::class, [
                                'product' => $product,
                                'productSku' => $sku
                            ]),
                            'productUnit' => $this->getEntity(ProductUnit::class, ['code' => $unit]),
                            'productUnitCode' => $unit,
                            'price' => Price::create($price, $currency)
                        ])
                    ]
                ])
            ]
        ];
    }

    /**
     * @param string $entityClass
     * @return  EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function configureRepository(string $entityClass)
    {
        $entityRepository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with($entityClass)
            ->willReturn($entityRepository);

        return $entityRepository;
    }
}
