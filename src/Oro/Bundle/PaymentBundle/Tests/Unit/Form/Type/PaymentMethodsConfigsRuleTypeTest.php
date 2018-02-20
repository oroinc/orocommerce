<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleType;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntax;
use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntaxValidator;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Symfony\Component\Form\PreloadedExtension;

class PaymentMethodsConfigsRuleTypeTest extends AddressFormExtensionTestCase
{
    const PAYMENT_TYPE = 'code1';
    const ADMIN_LABEL = 'admin_label1';

    /**
     * @var PaymentMethodsConfigsRuleType
     */
    protected $formType;

    /**
     * @var PaymentMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodProvider;

    /**
     * @var CompositePaymentMethodViewProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $compositePaymentMethodViewProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->createMocks();
        $this->formType = new PaymentMethodsConfigsRuleType(
            $this->paymentMethodProvider,
            $this->compositePaymentMethodViewProvider
        );
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PaymentMethodsConfigsRuleType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);
        $options = $form->getConfig()->getOptions();
        $this->assertContains('data_class', $options);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|null $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create($this->formType, $data);

        $this->assertEquals($data, $form->getData());

        $form->submit([
            'methodConfigs' => [['type' => self::PAYMENT_TYPE, 'options' => []]],
            'destinations' => [['country' => 'US']],
            'currency' => 'USD',
            'rule' => [
                'name' => 'rule2',
                'sortOrder' => '1',
            ],
        ]);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            (new PaymentMethodsConfigsRule())
                ->setCurrency('USD')
                ->setRule((new Rule())->setSortOrder(1)->setName('rule2')->setEnabled(false))
                ->addDestination((new PaymentMethodsConfigsRuleDestination())->setCountry(new Country('US')))
                ->addMethodConfig((new PaymentMethodConfig())->setType(self::PAYMENT_TYPE)),
            $form->getData()
        );
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [null],
            [
                (new PaymentMethodsConfigsRule())
                    ->setRule((new Rule())->setName('rule1'))
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensions()
    {
        $translatableEntity = $this->getTranslatableEntity();

        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD']);

        $this->createMocks();

        $subscriber = new RuleMethodConfigCollectionSubscriber($this->paymentMethodProvider);

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    RuleType::BLOCK_PREFIX => new RuleType(),
                    PaymentMethodConfigType::NAME =>
                        new PaymentMethodConfigType(
                            $this->paymentMethodProvider,
                            $this->compositePaymentMethodViewProvider
                        ),
                    PaymentMethodsConfigsRuleDestinationType::NAME =>
                        new PaymentMethodsConfigsRuleDestinationType(new AddressCountryAndRegionSubscriberStub()),
                    PaymentMethodConfigCollectionType::class =>
                        new PaymentMethodConfigCollectionType($subscriber),
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $currencyProvider,
                        $this->createMock(LocaleSettings::class),
                        $this->createMock(CurrencyNameHelper::class)
                    ),
                    'oro_country' => new CountryType(),
                    'oro_select2_translatable_entity' => new Select2Type(
                        'translatable_entity',
                        'oro_select2_translatable_entity'
                    ),
                    'translatable_entity' => $translatableEntity,
                    'oro_region' => new RegionType(),
                ],
                ['form' => [
                    new AdditionalAttrExtension(),
                    new StripTagsExtensionStub($this->createMock(HtmlTagHelper::class)),
                ]]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators()
    {
        $expressionLanguageSyntax = new ExpressionLanguageSyntax();

        return [
            $expressionLanguageSyntax->validatedBy() => $this->createMock(ExpressionLanguageSyntaxValidator::class),
        ];
    }

    protected function createMocks()
    {
        $this->compositePaymentMethodViewProvider = $this
            ->getMockBuilder(CompositePaymentMethodViewProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMockBuilder(PaymentMethodInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethod->expects(static::any())->method('getIdentifier')->willReturn(self::PAYMENT_TYPE);

        /** @var PaymentMethodViewInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethodView */
        $paymentMethodView = $this->getMockBuilder(PaymentMethodViewInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodView
            ->expects(static::any())
            ->method('getAdminLabel')
            ->willReturn(self::ADMIN_LABEL);

        $this->paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);
        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('hasPaymentMethod')
            ->with(self::PAYMENT_TYPE)
            ->willReturn(true);
        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('getPaymentMethod')
            ->with(self::PAYMENT_TYPE)
            ->willReturn($paymentMethod);

        $this->compositePaymentMethodViewProvider->expects(static::any())
            ->method('getPaymentMethodView')
            ->with(self::PAYMENT_TYPE)
            ->willReturn($paymentMethodView);
    }
}
