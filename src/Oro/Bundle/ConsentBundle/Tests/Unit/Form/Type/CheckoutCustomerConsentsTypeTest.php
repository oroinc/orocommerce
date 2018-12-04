<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\Type\CheckoutCustomerConsentsType;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutCustomerConsentsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const FEATURE_NAME = 'consents';
    const CUSTOMER_ACCEPTANCE_DATA = 'customer_acceptance_data';

    /** @var CheckoutCustomerConsentsType */
    private $formType;

    /** @var ConsentContextInitializeHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $consentContextInitializeHelper;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var CustomerUserExtractor */
    protected $customerUserExtractor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->consentContextInitializeHelper = $this->createMock(
            ConsentContextInitializeHelperInterface::class
        );
        /** @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject $consentAcceptanceProvider */
        $consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);
        $consentAcceptanceProvider
            ->expects($this->any())
            ->method('getCustomerConsentAcceptances')
            ->willReturn(self::CUSTOMER_ACCEPTANCE_DATA);

        $this->builder = $this->createMock(FormBuilderInterface::class);

        $this->customerUserExtractor = new CustomerUserExtractor();
        $this->customerUserExtractor->addMapping(Checkout::class, 'customerUser');
        $this->customerUserExtractor->addMapping(Checkout::class, 'registeredCustomerUser');

        $this->formType = new CheckoutCustomerConsentsType(
            $this->consentContextInitializeHelper,
            $consentAcceptanceProvider,
            $this->customerUserExtractor
        );

        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->formType->setFeatureChecker($this->featureChecker);
        $this->formType->addFeature(self::FEATURE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->consentContextInitializeHelper);
        unset($this->formType);
        unset($this->builder);
        unset($this->featureChecker);

        parent::tearDown();
    }

    public function testBuildFormWithFeatureDisabled()
    {
        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME, null)
            ->willReturn(false);

        $this->consentContextInitializeHelper
            ->expects($this->never())
            ->method('initialize');

        $this->builder
            ->expects($this->never())
            ->method('setData');

        $this->formType->buildForm($this->builder, []);
    }

    /**
     * @dataProvider buildFormWithFeatureEnabledProvider
     *
     * @param array $options
     * @param bool|CustomerUser $customerUser
     * @param bool  $expectedContextWillBeInitializer
     * @param bool  $expectedSetData
     */
    public function testBuildFormWithFeatureEnabled(
        array $options,
        $customerUser,
        bool $expectedContextWillBeInitializer,
        bool $expectedSetData
    ) {
        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME, null)
            ->willReturn(true);

        if ($expectedContextWillBeInitializer) {
            $this->consentContextInitializeHelper
                ->expects($this->once())
                ->method('initialize')
                ->with($customerUser);
        } else {
            $this->consentContextInitializeHelper
                ->expects($this->never())
                ->method('initialize');
        }

        if ($expectedSetData) {
            $this->builder
                ->expects($this->once())
                ->method('setData')
                ->with(self::CUSTOMER_ACCEPTANCE_DATA);
        } else {
            $this->builder
                ->expects($this->never())
                ->method('setData');
        }

        $this->formType->buildForm($this->builder, $options);
    }

    /**
     * @return array
     */
    public function buildFormWithFeatureEnabledProvider()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $checkoutCustomerUser = $this->getEntity(Checkout::class, [
            'id' => 1,
            'customerUser' => $customerUser
        ]);
        $checkoutRegisteredCustomerUser = $this->getEntity(Checkout::class, [
            'id' => 1,
            'registeredCustomerUser' => $customerUser
        ]);

        return [
            'Option "checkout" contains "false" value' => [
                'options' => [
                    'checkout' => false
                ],
                'customerUser' => false,
                'expectedContextWillBeInitializer' => false,
                'expectedSetData' => false
            ],
            'Option "checkout" contains "null" value' => [
                'options' => [
                    'checkout' => null
                ],
                'customerUser' => false,
                'expectedContextWillBeInitializer' => false,
                'expectedSetData' => false
            ],
            'Option "checkout" contains instance of CustomerUser in property "customerUser"' => [
                'options' => [
                    'checkout' => $checkoutCustomerUser
                ],
                'customerUser' => $customerUser,
                'expectedContextWillBeInitializer' => true,
                'expectedSetData' => true
            ],
            'Option "checkout" contains instance of CustomerUser in property "registeredCustomerUser"' => [
                'options' => [
                    'checkout' => $checkoutRegisteredCustomerUser
                ],
                'customerUser' => $customerUser,
                'expectedContextWillBeInitializer' => true,
                'expectedSetData' => true
            ],
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_checkout_customer_consents', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(CustomerConsentsType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([CheckoutCustomerConsentsType::CHECKOUT_OPTION_NAME => false]);

        $resolver->expects($this->once())
            ->method('addAllowedTypes')
            ->with(
                CheckoutCustomerConsentsType::CHECKOUT_OPTION_NAME,
                [Checkout::class, 'null', 'bool']
            );

        $resolver->expects($this->once())
            ->method('setDefined')
            ->with(CheckoutCustomerConsentsType::CHECKOUT_OPTION_NAME);

        $this->formType->configureOptions($resolver);
    }
}
