<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use Oro\Bundle\AccountBundle\Form\Type\AccountSelectType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class QuoteTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /**
     * @var QuoteType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuoteAddressSecurityProvider
     */
    protected $quoteAddressSecurityProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this
            ->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteAddressSecurityProvider = $this
            ->getMockBuilder(QuoteAddressSecurityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentTermProvider = $this
            ->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new QuoteType(
            $this->securityFacade,
            $this->quoteAddressSecurityProvider,
            $this->paymentTermProvider
        );
        $this->formType->setDataClass(Quote::class);
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'    => 'Oro\Bundle\SaleBundle\Entity\Quote',
                    'intention'     => 'sale_quote',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
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
     * @param int $accountUserId
     * @param int $accountId
     * @param QuoteProduct[] $items
     * @param bool $locked
     * @param string $poNumber
     * @param string $shipUntil
     * @return Quote
     */
    protected function getQuote(
        $ownerId,
        $accountUserId = null,
        $accountId = null,
        array $items = [],
        $locked = false,
        $poNumber = null,
        $shipUntil = null
    ) {
        $quote = new Quote();

        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface')->getMock();


        /** @var User $owner */
        $owner = $this->getEntity('Oro\Bundle\UserBundle\Entity\User', $ownerId);
        $owner->setUsername('UserName')
            ->setEmail('test@test.test')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setOrganization($organization);
        $quote->setOwner($owner);

        if (null !== $accountUserId) {
            $account = $this->getMockBuilder('Oro\Bundle\AccountBundle\Entity\Account')->getMock();
            $role = $this->getMockBuilder('Symfony\Component\Security\Core\Role\RoleInterface')->getMock();

            /** @var AccountUser $accountUser */
            $accountUser = $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', $accountUserId);
            $accountUser->setEmail('test@test.test')
                ->setFirstName('First Name')
                ->setLastName('Last Name')
                ->setUsername('test@test.test')
                ->setAccount($account)
                ->setRoles([$role])
            ->setOrganization($organization);
            $quote->setAccountUser($accountUser);
        }

        if (null !== $accountId) {
            /** @var Account $account */
            $account = $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', $accountId);
            $account->setName('Name');
            $quote->setAccount($account);
        }

        foreach ($items as $item) {
            $quote->addQuoteProduct($item);
        }
        $quote->setLocked($locked);

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

        return [
            'empty owner' => [
                'isValid'       => false,
                'submittedData' => [
                ],
                'expectedData'  => new Quote(),
                'defaultData'   => $this->getQuote(1),
                'options' => [
                    'data' => $this->getQuote(1)
                ]
            ],
            'empty PO number' => [
                'isValid'       => true,
                'submittedData' => [
                    'owner' => 1,
                    'accountUser' => 1,
                    'account' => 2,
                    'locked' => false,
                    'poNumber'  => null,
                    'shipUntil' => null,
                    'quoteProducts' => [
                        [
                            'product'   => 2,
                            'type'      => self::QP_TYPE1,
                            'comment'   => 'comment1',
                            'commentAccount' => 'comment2',
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
                ],
                'expectedData'  => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    false,
                    null,
                    null
                ),
                'defaultData'   => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    false,
                    null,
                    null
                ),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'owner' => 1,
                    'accountUser' => 1,
                    'account' => 2,
                    'locked' => false,
                    'poNumber'  => 'poNumber',
                    'shipUntil' => $date,
                    'quoteProducts' => [
                        [
                            'product'   => 2,
                            'type'      => self::QP_TYPE1,
                            'comment'   => 'comment1',
                            'commentAccount' => 'comment2',
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
                    'assignedAccountUsers' => [11],
                    'shippingEstimate' => [
                        'value' => 111.12,
                        'currency' => 'USD'
                    ]
                ],
                'expectedData'  => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    false,
                    'poNumber',
                    new \DateTime($date . 'T00:00:00+0000')
                )
                    ->addAssignedUser($this->getUser(1))
                    ->addAssignedAccountUser($this->getAccountUser(11))
                    ->setShippingEstimate(Price::create(111.12, 'USD')),
                'defaultData' => $this->getQuote(
                    1,
                    1,
                    2,
                    [$quoteProduct],
                    false,
                    'poNumber',
                    new \DateTime($date . 'T00:00:00+0000')
                )->addAssignedUser($this->getUser(1))
                    ->addAssignedAccountUser($this->getAccountUser(11))
                    ->setShippingEstimate(Price::create(111.12, 'USD')),
                'options' => [
                    'data' => $this->getQuote(
                        1,
                        1,
                        2,
                        [$quoteProduct],
                        false,
                        'poNumber',
                        new \DateTime($date . 'T00:00:00+0000')
                    )->addAssignedUser($this->getUser(1))
                        ->addAssignedAccountUser($this->getAccountUser(11))
                        ->setShippingEstimate(Price::create(111.12, 'USD')),
                ]
            ],
        ];
    }

    public function testBuildFormWithPaymetTerm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock(FormBuilderInterface::class);
        $quote = new Quote();
        $accountPaymentTerm = $this->getMock(PaymentTerm::class);
        $accountGroupPaymentTerm = $this->getMock(PaymentTerm::class);
        $accountGroup = new AccountGroup();
        $account = new Account();
        $account->setGroup($accountGroup);
        $quote->setAccount($account);
        $options = [
            'label' => 'oro.sale.quote.payment_term.label',
            'required' => false,
            'attr' => [
                'data-account-payment-term' => 10,
                'data-account-group-payment-term' => 100,
            ],
        ];

        $this
            ->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with('orob2b_quote_payment_term_account_can_override')
            ->willReturn(true);
        $accountPaymentTerm->expects($this->once())->method('getId')->willReturn(10);
        $accountGroupPaymentTerm->expects($this->once())->method('getId')->willReturn(100);
        $this
            ->paymentTermProvider
            ->expects($this->once())
            ->method('getAccountPaymentTerm')
            ->with($account)
            ->willReturn($accountPaymentTerm);
        $this
            ->paymentTermProvider
            ->expects($this->once())
            ->method('getAccountGroupPaymentTerm')
            ->with($accountGroup)
            ->willReturn($accountGroupPaymentTerm);
        $builder->expects($this->atMost(13))->method('add')->willReturn($builder);
        $builder
            ->expects($this->at(12))
            ->method('add')
            ->with('paymentTerm', PaymentTermSelectType::NAME, $options)
            ->willReturn($builder);

        $this->formType->buildForm($builder, ['data' => $quote]);
    }

    public function testBuildFormWithNoPaymetTerm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock(FormBuilderInterface::class);
        $quote = new Quote();

        $this
            ->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with('orob2b_quote_payment_term_account_can_override')
            ->willReturn(false);
        $this->paymentTermProvider->expects($this->never())->method('getAccountPaymentTerm');
        $this->paymentTermProvider->expects($this->never())->method('getAccountGroupPaymentTerm');
        $builder->expects($this->atMost(12))->method('add')->willReturn($builder);

        $this->formType->buildForm($builder, ['data' => $quote]);
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
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

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

        $accountSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 1),
                2 => $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 2),
            ],
            AccountSelectType::NAME
        );

        $accountUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', 1),
                2 => $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', 2),
            ],
            AccountUserSelectType::NAME
        );

        $priceListSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 1),
                2 => $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', 2),
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
        $accountUserMultiSelectType  = $this->prepareAccountUserMultiSelectType();

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
                    $accountUserMultiSelectType->getName()      => $accountUserMultiSelectType,
                    $accountSelectType->getName()               => $accountSelectType,
                    $accountUserSelectType->getName()           => $accountUserSelectType,
                    $priceListSelectType->getName()             => $priceListSelectType,
                    QuantityTypeTrait::$name                    => $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
