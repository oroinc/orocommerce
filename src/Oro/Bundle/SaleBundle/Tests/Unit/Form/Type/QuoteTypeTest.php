<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class QuoteTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var QuoteType */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QuoteAddressSecurityProvider */
    protected $quoteAddressSecurityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QuoteFormSubscriber */
    protected $quoteFormSubscriber;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

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

        $this->securityFacade = $this->createMock(SecurityFacade::class);

        $this->formType = new QuoteType(
            $this->quoteAddressSecurityProvider,
            $this->configManager,
            $this->quoteFormSubscriber,
            $this->securityFacade
        );

        $this->formType->setDataClass(Quote::class);
    }

    public function testConfigureOptions()
    {
        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->with('oro_quote_prices_override')
            ->willReturn(true);
        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->with('oro_quote_add_free_form_items')
            ->willReturn(false);
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\SaleBundle\Entity\Quote',
                    'intention' => 'sale_quote',
                    'allow_prices_override' => true,
                    'allow_add_free_form_items' => false,
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteType::NAME, $this->formType->getName());
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

        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface')->getMock();

        /** @var User $owner */
        $owner = $this->getEntity('Oro\Bundle\UserBundle\Entity\User', $ownerId);
        $owner->setUsername('UserName')
            ->setEmail('test@test.test')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setOrganization($organization);
        $quote->setOwner($owner);

        if (null !== $customerUserId) {
            $customer = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Customer')->getMock();
            $role = $this->getMockBuilder('Symfony\Component\Security\Core\Role\RoleInterface')->getMock();

            /** @var CustomerUser $customerUser */
            $customerUser = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', $customerUserId);
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
            $customer = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', $customerId);
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
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
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
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
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
        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /* @var $registry ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        /* @var $productUnitLabelFormatter \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $userSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1),
                2 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2),
            ],
            'oro_user_select'
        );

        $customerSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', 1),
                2 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', 2),
            ],
            CustomerSelectType::NAME
        );

        $customerUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', 1),
                2 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', 2),
            ],
            CustomerUserSelectType::NAME
        );

        $priceListSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 1),
                2 => $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 2),
            ],
            PriceListSelectType::class
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
        $quoteProductType->setDataClass('Oro\Bundle\SaleBundle\Entity\QuoteProduct');

        return [
            new PreloadedExtension(
                [
                    OroDateTimeType::NAME                       => new OroDateTimeType(),
                    CollectionType::NAME                        => new CollectionType(),
                    QuoteProductOfferType::NAME                 => new QuoteProductOfferType(
                        $this->quoteProductOfferFormatter
                    ),
                    QuoteProductCollectionType::NAME            => new QuoteProductCollectionType(),
                    QuoteProductOfferCollectionType::NAME       => new QuoteProductOfferCollectionType(),
                    QuoteProductRequestCollectionType::NAME     => new QuoteProductRequestCollectionType(),
                    ProductUnitSelectionType::NAME              => new ProductUnitSelectionTypeStub(),
                    OroDateType::NAME                           => new OroDateType(),
                    $priceType->getName()                       => $priceType,
                    $entityType->getName()                      => $entityType,
                    $userSelectType->getName()                  => $userSelectType,
                    $quoteProductType->getName()                => $quoteProductType,
                    $productSelectType->getName()               => $productSelectType,
                    $userMultiSelectType->getName()             => $userMultiSelectType,
                    $currencySelectionType->getName()           => $currencySelectionType,
                    $quoteProductOfferType->getName()           => $quoteProductOfferType,
                    $quoteProductRequestType->getName()         => $quoteProductRequestType,
                    $productUnitSelectionType->getName()        => $productUnitSelectionType,
                    $customerUserMultiSelectType->getName()      => $customerUserMultiSelectType,
                    $customerSelectType->getName()               => $customerSelectType,
                    $customerUserSelectType->getName()           => $customerUserSelectType,
                    $priceListSelectType->getName()             => $priceListSelectType,
                    QuantityTypeTrait::$name                    => $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
