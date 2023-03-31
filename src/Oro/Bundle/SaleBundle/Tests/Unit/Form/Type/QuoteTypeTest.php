<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
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
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\SecurityBundle\Model\Role;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class QuoteTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var QuoteAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteAddressSecurityProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var MutableFormEventSubscriber */
    private $quoteFormSubscriber;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var QuoteType */
    private $formType;

    protected function setUp(): void
    {
        $this->quoteAddressSecurityProvider = $this->createMock(QuoteAddressSecurityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->quoteFormSubscriber = new MutableFormEventSubscriber($this->createMock(QuoteFormSubscriber::class));
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn('USD');

        $this->configureQuoteProductOfferFormatter();

        $this->formType = new QuoteType(
            $this->quoteAddressSecurityProvider,
            $this->configManager,
            $this->quoteFormSubscriber,
            $this->authorizationChecker
        );

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['oro_quote_prices_override', null, true],
                ['oro_quote_add_free_form_items', null, false]
            ]);
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
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

    private function getQuote(
        int $ownerId,
        int $customerUserId = null,
        int $customerId = null,
        array $items = [],
        string $poNumber = null,
        \DateTime $shipUntil = null,
        bool $shippingMethodLocked = false,
        bool $allowedUnlistedShippingMethod = false
    ): Quote {
        $quote = new Quote();

        $quote->setShippingMethodLocked($shippingMethodLocked);
        $quote->setAllowUnlistedShippingMethod($allowedUnlistedShippingMethod);

        $organization = $this->createMock(OrganizationInterface::class);

        $owner = $this->getUser($ownerId);
        $owner->setUsername('UserName')
            ->setEmail('test@test.test')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setOrganization($organization);
        $quote->setOwner($owner);

        if (null !== $customerUserId) {
            $customerUser = $this->getTestCustomerUser($customerUserId);
            $customerUser->setOrganization($organization);
            $quote->setCustomerUser($customerUser);
        }

        if (null !== $customerId) {
            $customer = $this->getCustomer($customerId);
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
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider(): array
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
                'defaultData'   => $this->getQuote(1)
                    ->setCurrency('USD')
                    ->setGuestAccessId($quote->getGuestAccessId()),
                'options' => [
                    'data' => $this->getQuote(1)
                        ->setGuestAccessId($quote->getGuestAccessId())
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
                'expectedData'  => $this->getQuote(1, 1, 2, [$quoteProduct], null, null, true, true)
                    ->setCurrency('USD')
                    ->setGuestAccessId($quote->getGuestAccessId()),
                'defaultData'   => $this->getQuote(1, 1, 2, [$quoteProduct])
                    ->setGuestAccessId($quote->getGuestAccessId())
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
                    ->addAssignedCustomerUser($this->getTestCustomerUser(11))
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
                )
                    ->addAssignedUser($this->getUser(1))
                    ->addAssignedCustomerUser($this->getTestCustomerUser(11))
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
                    )
                        ->addAssignedUser($this->getUser(1))
                        ->addAssignedCustomerUser($this->getTestCustomerUser(11))
                        ->setGuestAccessId($quote->getGuestAccessId()),
                ]
            ],
        ];
    }

    public function testBuildFormWithPaymentTerm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $quote = new Quote();
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $quote->setCustomer($customer);

        $builder->expects($this->atMost(18))
            ->method('add')
            ->willReturn($builder);
        $builder->expects(self::once())
            ->method('get')
            ->willReturn($builder);
        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with($this->quoteFormSubscriber);

        $this->formType->buildForm(
            $builder,
            ['data' => $quote, 'allow_prices_override' => true, 'allow_add_free_form_items' => true]
        );
    }

    public function testBuildFormWithNoPaymentTerm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $quote = new Quote();

        $builder->expects($this->atMost(18))
            ->method('add')
            ->willReturn($builder);
        $builder->expects(self::once())
            ->method('get')
            ->willReturn($builder);
        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with($this->quoteFormSubscriber);

        $this->formType->buildForm(
            $builder,
            ['data' => $quote, 'allow_prices_override' => true, 'allow_add_free_form_items' => true]
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function createForm(mixed $data, array $options): FormInterface
    {
        return $this->factory->create(QuoteType::class, $data, $options);
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
                    $this->getPriceType(),
                    EntityType::class => $this->getProductEntityType(),
                    UserSelectType::class => new EntityTypeStub([
                        1 => $this->getUser(1),
                        2 => $this->getUser(2),
                    ]),
                    new QuoteProductType(
                        $this->createMock(UnitLabelFormatterInterface::class),
                        $this->createMock(ManagerRegistry::class)
                    ),
                    ProductSelectType::class => new ProductSelectTypeStub(),
                    UserMultiSelectType::class => new EntityTypeStub(
                        [1 => $this->getUser(1), 2 => $this->getUser(2)],
                        ['multiple' => true]
                    ),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    new QuoteProductOfferType(),
                    new QuoteProductRequestType(),
                    ProductUnitSelectionType::class => $this->getProductUnitSelectionType(),
                    CustomerUserMultiSelectType::class => new EntityTypeStub(
                        [10 => $this->getTestCustomerUser(10), 11 => $this->getTestCustomerUser(11)],
                        ['multiple' => true]
                    ),
                    CustomerSelectType::class => new EntityTypeStub([
                        1 => $this->getCustomer(1),
                        2 => $this->getCustomer(2),
                    ]),
                    CustomerUserSelectType::class => new EntityTypeStub([
                        1 => $this->getCustomerUser(1),
                        2 => $this->getCustomerUser(2),
                    ]),
                    PriceListSelectType::class => new EntityTypeStub([
                        1 => $this->getPriceList(1),
                        2 => $this->getPriceList(2),
                    ]),
                    $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    private function getCustomer(int $id): Customer
    {
        return $this->getEntity(Customer::class, $id);
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        return $this->getEntity(CustomerUser::class, $id);
    }

    private function getTestCustomerUser(int $id): CustomerUser
    {
        $customerUser = $this->getCustomerUser($id);
        $customerUser
            ->setEmail('test@test.test')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setUsername('test@test.test')
            ->setCustomer($this->createMock(Customer::class))
            ->setUserRoles([$this->createMock(Role::class)])
            ->setOrganization($this->createMock(OrganizationInterface::class));

        return $customerUser;
    }

    private function getPriceList(int $id): PriceList
    {
        return $this->getEntity(PriceList::class, $id);
    }
}
