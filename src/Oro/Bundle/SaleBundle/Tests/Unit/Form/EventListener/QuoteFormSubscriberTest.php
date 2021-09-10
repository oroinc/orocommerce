<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
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
use Symfony\Component\Form\Form;
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

    private const WEBSITE_ID = 2;
    private const CUSTOMER_ID = 4;
    private const PRODUCT_SKU = 'test-sku';
    private const PRODUCT_ID = 137;
    private const PRICE1 = 100;
    private const PRICE2 = 200;
    private const CURRENCY = 'USD';
    private const QUANTITY = 10;
    private const UNIT1 = 'kg';
    private const UNIT2 = 'set';

    /** @var QuoteProductPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private QuoteProductPriceProvider $provider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private DoctrineHelper $doctrineHelper;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private ProductRepository $productRepository;

    private QuoteFormSubscriber $subscriber;

    private array $tierPrices = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->createMock(QuoteProductPriceProvider::class);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())->method('trans')->willReturnArgument(0);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->subscriber = new QuoteFormSubscriber(
            $this->provider,
            $translator,
            $this->doctrineHelper
        );

        $this->tierPrices = [
            self::PRODUCT_ID => [
                new ProductPriceDTO(
                    (new ProductStub())->setId(self::PRODUCT_ID),
                    Price::create(self::PRICE2, self::CURRENCY),
                    1,
                    (new ProductUnit())->setCode(self::UNIT2)
                ),
                new ProductPriceDTO(
                    (new ProductStub())->setId(self::PRODUCT_ID),
                    Price::create(self::PRICE2, self::CURRENCY),
                    20,
                    (new ProductUnit())->setCode(self::UNIT2)
                ),
            ],
        ];
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::PRE_SUBMIT => 'onPreSubmit',
                FormEvents::SUBMIT => 'onSubmit',
            ],
            QuoteFormSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider noWebsiteDataProvider
     */
    public function testOnPreSubmitWithNoWebsite(?array $data): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('getData')->willReturn($quote);

        $this->subscriber->onPreSubmit(new FormEvent($form, $data));

        self::assertNull($quote->getWebsite());
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

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('getData')->willReturn($quote);

        $website = new Website();
        $entityRepository = $this->configureRepository(Website::class);
        $entityRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::WEBSITE_ID)
            ->willReturn($website);

        $this->subscriber->onPreSubmit(new FormEvent($form, ['website' => self::WEBSITE_ID]));

        self::assertSame($website, $quote->getWebsite());
    }

    /**
     * @dataProvider noCustomerDataProvider
     */
    public function testOnPreSubmitWithNoCustomer(?array $data): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('getData')->willReturn($quote);

        $this->subscriber->onPreSubmit(new FormEvent($form, $data));

        self::assertNull($quote->getCustomer());
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

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('getData')->willReturn($quote);

        $customer = new Customer();
        $entityRepository = $this->configureRepository(Customer::class);
        $entityRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::CUSTOMER_ID)
            ->willReturn($customer);

        $this->subscriber->onPreSubmit(new FormEvent($form, ['customer' => self::CUSTOMER_ID]));

        self::assertSame($customer, $quote->getCustomer());
    }

    /**
     * @dataProvider onSubmitProvider
     *
     * @param array $data
     * @param array $options
     * @param bool $expectPriceChange
     */
    public function testOnSubmit(array $data, array $options = [], $expectPriceChange = false): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, $data);

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects(self::any())->method('getOptions')->willReturn($options);

        $form = new Form($config);

        if (!$data['quoteProducts']) {
            $this->provider->expects(self::never())
                ->method('getMatchedProductPrice');
        } else {
            $price = $this->getEntity(Price::class, ['value' => self::PRICE1]);
            $matchedPrice = $expectPriceChange ? null : $price;
            $this->provider->expects(self::once())
                ->method('getMatchedProductPrice')
                ->willReturn($matchedPrice);
        }

        $this->provider->expects(self::never())
            ->method('getTierPricesForProducts');

        $this->subscriber->onSubmit(new FormEvent($form, $quote));

        self::assertEquals($expectPriceChange, $quote->isPricesChanged());
    }

    public function onSubmitProvider(): array
    {
        return [
            'no products' => [
                'data' => ['quoteProducts' => []],
                'options' => [],
                'expectPriceChange' => false,
            ],
            'no changes' => [
                'data' => $this->getData(),
                'options' => [],
                'expectPriceChange' => false,
            ],
            'price changed' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => true],
                'expectPriceChange' => true,
            ],
            'price changed not allow free form' => [
                'data' => $this->getData(self::PRICE2),
                'options' => ['allow_prices_override' => true, 'allow_add_free_form_items' => false],
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
                'expectPriceChange' => true,
            ],
        ];
    }

    public function testOnSubmitWhenFreeFormNotAllowed(): void
    {
        $data = $this->getData(self::PRICE2, self::CURRENCY, self::QUANTITY, self::UNIT1, self::PRODUCT_SKU, true);

        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, $data);

        $config = $this->createMock(FormConfigInterface::class);
        $config
            ->expects(self::any())
            ->method('getOptions')
            ->willReturn(['allow_prices_override' => true, 'allow_add_free_form_items' => false]);

        $form = new Form($config);

        $this->provider->expects(self::once())
            ->method('getMatchedProductPrice')
            ->willReturn(null);

        $this->provider->expects(self::never())
            ->method('getTierPricesForProducts');

        $this->subscriber->onSubmit(new FormEvent($form, $quote));

        self::assertTrue($quote->isPricesChanged());

        $formError = new FormError('oro.sale.quote.form.error.free_form_price_override');
        $formError->setOrigin($form);
        self::assertContainsEquals(
            $formError,
            iterator_to_array($form->getErrors(true, true))
        );
    }

    public function testOnSubmitWithCheckingTierPrice(): void
    {
        $data = $this->getData(self::PRICE2, self::CURRENCY, 5, self::UNIT2);

        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, $data);

        $config = $this->createMock(FormConfigInterface::class);
        $config
            ->expects(self::any())
            ->method('getOptions')
            ->willReturn(['allow_prices_override' => false, 'allow_add_free_form_items' => false]);

        $form = new Form($config);

        $product = new ProductStub();
        $product->setId(self::PRODUCT_ID);
        $product->setSku(self::PRODUCT_SKU);

        $this->provider
            ->expects(self::once())
            ->method('getTierPricesForProducts')
            ->with($quote, [$product])
            ->willReturn($this->tierPrices);

        $this->subscriber->onSubmit(new FormEvent($form, $quote));

        self::assertTrue($quote->isPricesChanged());
    }

    public function testOnSubmitWithCheckingTierPriceAndError(): void
    {
        $data = $this->getData(self::PRICE2);

        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, $data);

        $config = $this->createMock(FormConfigInterface::class);
        $config
            ->expects(self::any())
            ->method('getOptions')
            ->willReturn(['allow_prices_override' => false, 'allow_add_free_form_items' => true]);

        $form = new Form($config);

        $product = new ProductStub();
        $product->setId(self::PRODUCT_ID);
        $product->setSku(self::PRODUCT_SKU);

        $this->provider
            ->expects(self::once())
            ->method('getTierPricesForProducts')
            ->with($quote, [$product])
            ->willReturn($this->tierPrices);

        $this->subscriber->onSubmit(new FormEvent($form, $quote));

        self::assertTrue($quote->isPricesChanged());

        $formError = new FormError('oro.sale.quote.form.error.price_override');
        $formError->setOrigin($form);
        self::assertContainsEquals(
            $formError,
            iterator_to_array($form->getErrors(true, true))
        );
    }

    public function testOnSubmitSkip(): void
    {
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects(self::any())->method('getOptions')->willReturn([]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())->method('getConfig')->willReturn($config);
        $form->expects(self::never())->method('addError');

        $this->provider->expects(self::never())->method('getTierPricesForProducts');

        $this->subscriber->onSubmit(new FormEvent($form, null));
    }

    public function testOnSubmitNewQuoteWithoutWebsiteAndCustomerData(): void
    {
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects(self::any())->method('getOptions')->willReturn([]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())->method('getConfig')->willReturn($config);
        $form->expects(self::never())->method('addError');

        $data = $this->getData(self::PRICE2, self::CURRENCY, 5, self::UNIT2);
        $quote = $this->getEntity(Quote::class, $data);

        $product = new ProductStub();
        $product->setId(self::PRODUCT_ID);
        $product->setSku(self::PRODUCT_SKU);

        $this->provider->expects(self::once())
            ->method('getTierPricesForProducts')
            ->with($quote, [$product])
            ->willReturn($this->tierPrices);

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
    ): array {
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
     * @return EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function configureRepository(string $entityClass): EntityRepository
    {
        $entityRepository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepository')
            ->with($entityClass)
            ->willReturn($entityRepository);

        return $entityRepository;
    }
}
