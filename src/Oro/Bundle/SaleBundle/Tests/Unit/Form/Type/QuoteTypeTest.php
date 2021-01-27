<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var QuoteType */
    protected $formType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QuoteAddressSecurityProvider */
    protected $quoteAddressSecurityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QuoteFormSubscriber */
    protected $quoteFormSubscriber;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->quoteAddressSecurityProvider = $this->createMock(QuoteAddressSecurityProvider::class);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn('USD');

        $this->quoteFormSubscriber = $this->createMock(QuoteFormSubscriber::class);
        $this->quoteFormSubscriber = new MutableFormEventSubscriber($this->quoteFormSubscriber);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn('USD');

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->configureQuoteProductOfferFormatter();

        $this->formType = new QuoteType(
            $this->quoteAddressSecurityProvider,
            $this->configManager,
            $this->quoteFormSubscriber,
            $this->authorizationChecker
        );

        $this->formType->setDataClass(Quote::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('oro_quote_prices_override')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->at(1))
            ->method('isGranted')
            ->with('oro_quote_add_free_form_items')
            ->willReturn(false);
        /* @var $resolver \PHPUnit\Framework\MockObject\MockObject|OptionsResolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => Quote::class,
                    'csrf_token_id' => 'sale_quote',
                    'allow_prices_override' => true,
                    'allow_add_free_form_items' => false,
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @param int $ownerId
     * @param int $customerUserId
     * @param int $customerId
     * @param QuoteProduct[] $items
     * @param string $poNumber
     * @param string $shipUntil
     * @param bool $shippingMethodLocked
     * @param bool $allowedUnlistedShippingMethod
     * @return Quote
     */
    protected function getQuote(
        $ownerId,
        $customerUserId = null,
        $customerId = null,
        array $items = [],
        $poNumber = null,
        $shipUntil = null,
        $shippingMethodLocked = false,
        $allowedUnlistedShippingMethod = false
    ) {
        $quote = new Quote();

        $quote->setShippingMethodLocked($shippingMethodLocked);
        $quote->setAllowUnlistedShippingMethod($allowedUnlistedShippingMethod);

        $organization = $this->createMock(OrganizationInterface::class);

        /** @var User $owner */
        $owner = $this->getEntity(User::class, $ownerId);
        $owner->setUsername('UserName')
            ->setEmail('test@test.test')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setOrganization($organization);
        $quote->setOwner($owner);

        if (null !== $customerUserId) {
            $customer = $this->createMock(Customer::class);
            $role = $this->createMock(Role::class);

            /** @var CustomerUser $customerUser */
            $customerUser = $this->getEntity(CustomerUser::class, $customerUserId);
            $customerUser->setEmail('test@test.test')
                ->setFirstName('First Name')
                ->setLastName('Last Name')
                ->setUsername('test@test.test')
                ->setCustomer($customer)
                ->setRoles([$role])
            ->setOrganization($organization);
            $quote->setCustomerUser($customerUser);
        }

        if (null !== $customerId) {
            /** @var Customer $customer */
            $customer = $this->getEntity(Customer::class, $customerId);
            $customer->setName('Name');
            $quote->setCustomer($customer);
        }

        foreach ($items as $item) {
            $quote->addQuoteProduct($item);
        }

        if (null !== $poNumber) {
            $quote->setPoNumber($poNumber);
        }

        if (null !== $shipUntil) {
            $quote->setShipUntil($shipUntil);
        }

        return $quote;
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider()
    {
        $quoteProductOffer = $this->getQuoteProductOffer(2, 33, 'kg', self::QPO_PRICE_TYPE1, Price::create(44, 'USD'));
        $quoteProduct = $this->getQuoteProduct(2, self::QP_TYPE1, 'comment1', 'comment2', [], [$quoteProductOffer]);

        $date = '2015-10-15';

        $quote = new Quote();
        $quote->setCurrency('USD');

        return [
            'empty owner' => [
                'isValid'       => false,
                'submittedData' => [
                ],
                'expectedData'  => $quote,
                'defaultData'   => $this->getQuote(1)->setCurrency('USD')->setGuestAccessId($quote->getGuestAccessId()),
                'options' => [
                    'data' => $this->getQuote(1)->setGuestAccessId($quote->getGuestAccessId())
                ]
            ],
            'empty PO number' => [
                'isValid'       => true,
                'submittedData' => [
                    'owner' => 1,
                    'customerUser' => 1,
                    'customer' => 2,
                    'poNumber'  => null,
                    'shipUntil' => null,
                    'quoteProducts' => [
                        [
                            'product'   => 2,
                            'type'      => self::QP_TYPE1,
                            'comment'   => 'comment1',
                            'commentCustomer' => 'comment2',
                            'quoteProductOffers' => [
                                [
                                    'quantity'      => 33,
                                    'productUnit'   => 'kg',
                                    'priceType'     => self::QPO_PRICE_TYPE1,
                                    'price'         => [
                                        'value'     => 44,
                                        'currency'  => 'USD',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'shippingMethodLocked' => true,
                    'allowUnlistedShippingMethod' => true
                ],
                'expectedData'  => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    null,
                    null,
                    true,
                    true
                )
                    ->setCurrency('USD')
                    ->setGuestAccessId($quote->getGuestAccessId()),
                'defaultData'   => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    null,
                    null
                )->setGuestAccessId($quote->getGuestAccessId()),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'owner' => 1,
                    'customerUser' => 1,
                    'customer' => 2,
                    'poNumber'  => 'poNumber',
                    'shipUntil' => $date,
                    'quoteProducts' => [
                        [
                            'product'   => 2,
                            'type'      => self::QP_TYPE1,
                            'comment'   => 'comment1',
                            'commentCustomer' => 'comment2',
                            'quoteProductOffers' => [
                                [
                                    'quantity'      => 33,
                                    'productUnit'   => 'kg',
                                    'priceType'     => self::QPO_PRICE_TYPE1,
                                    'price'         => [
                                        'value'     => 44,
                                        'currency'  => 'USD',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'assignedUsers' => [1],
                    'assignedCustomerUsers' => [11],
                    'shippingMethod' => 'shippingMethod1',
                    'shippingMethodType' => 'shippingType1',
                    'estimatedShippingCostAmount' => 10,
                    'overriddenShippingCostAmount' => [
                        'value' => 111.12,
                        'currency' => 'USD',
                    ]
                ],
                'expectedData'  => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    'poNumber',
                    new \DateTime($date . 'T00:00:00+0000')
                )
                    ->addAssignedUser($this->getUser(1))
                    ->addAssignedCustomerUser($this->getCustomerUser(11))
                    ->setShippingMethod('shippingMethod1')
                    ->setShippingMethodType('shippingType1')
                    ->setCurrency('USD')
                    ->setEstimatedShippingCostAmount(10)
                    ->setOverriddenShippingCostAmount(111.12)
                    ->setGuestAccessId($quote->getGuestAccessId()),
                'defaultData' => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    'poNumber',
                    new \DateTime($date . 'T00:00:00+0000')
                )->addAssignedUser($this->getUser(1))
                    ->addAssignedCustomerUser($this->getCustomerUser(11))
                    ->setCurrency('USD')
                    ->setGuestAccessId($quote->getGuestAccessId()),
                'options' => [
                    'data' => $this->getQuote(
                        1,
                        1,
                        2,
                        [$quoteProduct],
                        'poNumber',
                        new \DateTime($date . 'T00:00:00+0000')
                    )->addAssignedUser($this->getUser(1))
                        ->addAssignedCustomerUser($this->getCustomerUser(11))
                        ->setGuestAccessId($quote->getGuestAccessId()),
                ]
            ],
        ];
    }

    public function testBuildFormWithPaymentTerm()
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $quote = new Quote();
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $quote->setCustomer($customer);

        $builder->expects($this->atMost(18))->method('add')->willReturn($builder);
        $builder->expects($this->once())->method('get')->willReturn($builder);
        $builder->expects($this->once())->method('addEventSubscriber')->with($this->quoteFormSubscriber);

        $this->formType->buildForm(
            $builder,
            ['data' => $quote, 'allow_prices_override' => true, 'allow_add_free_form_items' => true]
        );
    }

    public function testBuildFormWithNoPaymentTerm()
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $quote = new Quote();

        $builder->expects($this->atMost(18))->method('add')->willReturn($builder);
        $builder->expects($this->once())->method('get')->willReturn($builder);
        $builder->expects($this->once())->method('addEventSubscriber')->with($this->quoteFormSubscriber);

        $this->formType->buildForm(
            $builder,
            ['data' => $quote, 'allow_prices_override' => true, 'allow_add_free_form_items' => true]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getExtensions()
    {
        /* @var $translator \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
        $translator = $this->createMock(TranslatorInterface::class);

        /* @var $registry ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
        $registry = $this->createMock(ManagerRegistry::class);

        /* @var $productUnitLabelFormatter \PHPUnit\Framework\MockObject\MockObject|UnitLabelFormatterInterface */
        $productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $userSelectType = new StubEntityType(
            [
                1 => $this->getEntity(User::class, 1),
                2 => $this->getEntity(User::class, 2),
            ],
            'oro_user_select'
        );

        $customerSelectType = new StubEntityType(
            [
                1 => $this->getEntity(Customer::class, 1),
                2 => $this->getEntity(Customer::class, 2),
            ],
            CustomerSelectType::NAME
        );

        $customerUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity(CustomerUser::class, 1),
                2 => $this->getEntity(CustomerUser::class, 2),
            ],
            CustomerUserSelectType::NAME
        );

        $priceListSelectType = new StubEntityType(
            [
                1 => $this->getEntity(PriceList::class, 1),
                2 => $this->getEntity(PriceList::class, 2),
            ],
            PriceListSelectType::NAME
        );

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $productSelectType          = new ProductSelectTypeStub();
        $userMultiSelectType        = $this->prepareUserMultiSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $quoteProductOfferType      = $this->prepareQuoteProductOfferType();
        $quoteProductRequestType    = $this->prepareQuoteProductRequestType();
        $customerUserMultiSelectType  = $this->prepareCustomerUserMultiSelectType();

        $quoteProductType = new QuoteProductType(
            $translator,
            $productUnitLabelFormatter,
            $this->quoteProductFormatter,
            $registry
        );
        $quoteProductType->setDataClass(QuoteProduct::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    PriceType::class => $priceType,
                    EntityType::class => $entityType,
                    UserSelectType::class => $userSelectType,
                    QuoteProductType::class => $quoteProductType,
                    ProductSelectType::class => $productSelectType,
                    UserMultiSelectType::class => $userMultiSelectType,
                    CurrencySelectionType::class => $currencySelectionType,
                    QuoteProductOfferType::class => $quoteProductOfferType,
                    QuoteProductRequestType::class => $quoteProductRequestType,
                    ProductUnitSelectionType::class => $productUnitSelectionType,
                    CustomerUserMultiSelectType::class => $customerUserMultiSelectType,
                    CustomerSelectType::class => $customerSelectType,
                    CustomerUserSelectType::class => $customerUserSelectType,
                    PriceListSelectType::class => $priceListSelectType,
                    QuantityType::class => $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
