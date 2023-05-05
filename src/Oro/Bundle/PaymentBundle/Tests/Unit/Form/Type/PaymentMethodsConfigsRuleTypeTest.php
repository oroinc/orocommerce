<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
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
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\Constraints\ExpressionLanguageSyntaxValidator;

class PaymentMethodsConfigsRuleTypeTest extends AddressFormExtensionTestCase
{
    private const PAYMENT_TYPE = 'code1';
    private const ADMIN_LABEL = 'admin_label1';

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var CompositePaymentMethodViewProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $compositePaymentMethodViewProvider;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->compositePaymentMethodViewProvider = $this->createMock(CompositePaymentMethodViewProvider::class);

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->configurePaymentMethodProvider();
        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class);
        $this->assertEquals(PaymentMethodsConfigsRuleType::BLOCK_PREFIX, $form->getConfig()->getName());
    }

    public function testDefaultOptions()
    {
        $this->configurePaymentMethodProvider();
        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class);
        $options = $form->getConfig()->getOptions();
        self::assertContainsEquals('data_class', $options);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?PaymentMethodsConfigsRule $data)
    {
        $this->configurePaymentMethodProvider();
        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class, $data);

        $this->assertEquals($data, $form->getData());

        $form->submit([
            'methodConfigs' => [['type' => self::PAYMENT_TYPE, 'options' => ['option' => 1]]],
            'destinations' => [['country' => 'US']],
            'currency' => 'USD',
            'rule' => [
                'name' => 'rule2',
                'sortOrder' => '1',
            ],
        ]);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            (new PaymentMethodsConfigsRule())
                ->setCurrency('USD')
                ->setRule((new Rule())->setSortOrder(1)->setName('rule2')->setEnabled(false))
                ->addDestination((new PaymentMethodsConfigsRuleDestination())->setCountry(new Country('US')))
                ->addMethodConfig(
                    (new PaymentMethodConfig())
                    ->setType(self::PAYMENT_TYPE)
                    ->setOptions(['option' => 1])
                ),
            $form->getData()
        );
    }

    public function submitDataProvider(): array
    {
        return [
            [null],
            [(new PaymentMethodsConfigsRule())->setRule((new Rule())->setName('rule1'))],
        ];
    }

    public function testBuildViewForMethodsWithSameAdminLabel()
    {
        $firstPaymentMethod = $this->createPaymentMethod('identifier1');
        $secondPaymentMethod = $this->createPaymentMethod('identifier2');

        $firstPaymentMethodView = $this->createPaymentMethodView(self::ADMIN_LABEL);
        $secondPaymentMethodView = $this->createPaymentMethodView(self::ADMIN_LABEL);

        $this->paymentMethodProvider->expects(self::any())
            ->method('getPaymentMethods')
            ->willReturn([$firstPaymentMethod, $secondPaymentMethod]);

        $this->compositePaymentMethodViewProvider->expects(self::any())
            ->method('getPaymentMethodView')
            ->willReturnMap([
                ['identifier1', $firstPaymentMethodView],
                ['identifier2', $secondPaymentMethodView],
            ]);

        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class, null);
        $formView = $form->createView();

        $expectedChoices = [
            'identifier1' => self::ADMIN_LABEL,
            'identifier2' => self::ADMIN_LABEL . ' '
        ];

        self::assertEquals($expectedChoices, $formView->vars['methods']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD']);

        $subscriber = new RuleMethodConfigCollectionSubscriber($this->paymentMethodProvider);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        PaymentMethodsConfigsRuleType::class => new PaymentMethodsConfigsRuleType(
                            $this->paymentMethodProvider,
                            $this->compositePaymentMethodViewProvider
                        ),
                        CollectionType::class => new CollectionType(),
                        RuleType::BLOCK_PREFIX => new RuleType(),
                        PaymentMethodConfigType::class => new PaymentMethodConfigType(
                            $this->paymentMethodProvider,
                            $this->compositePaymentMethodViewProvider
                        ),
                        PaymentMethodsConfigsRuleDestinationType::class =>
                            new PaymentMethodsConfigsRuleDestinationType(new AddressCountryAndRegionSubscriberStub()),
                        PaymentMethodConfigCollectionType::class =>
                            new PaymentMethodConfigCollectionType($subscriber),
                        CurrencySelectionType::class => new CurrencySelectionType(
                            $currencyProvider,
                            $this->createMock(LocaleSettings::class),
                            $this->createMock(CurrencyNameHelper::class)
                        ),
                    ],
                    [FormType::class => [
                        new AdditionalAttrExtension(),
                        new StripTagsExtensionStub($this),
                    ]]
                ),
                $this->getValidatorExtension(true)
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        return [
            ExpressionLanguageSyntaxValidator::class => $this->createMock(ExpressionLanguageSyntaxValidator::class),
        ];
    }

    private function configurePaymentMethodProvider(): void
    {
        $paymentMethod = $this->createPaymentMethod(self::PAYMENT_TYPE);
        $paymentMethodView = $this->createPaymentMethodView(self::ADMIN_LABEL);

        $this->paymentMethodProvider->expects(self::any())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);
        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with(self::PAYMENT_TYPE)
            ->willReturn(true);
        $this->paymentMethodProvider->expects(self::any())
            ->method('getPaymentMethod')
            ->with(self::PAYMENT_TYPE)
            ->willReturn($paymentMethod);

        $this->compositePaymentMethodViewProvider->expects(self::any())
            ->method('getPaymentMethodView')
            ->with(self::PAYMENT_TYPE)
            ->willReturn($paymentMethodView);
    }

    private function createPaymentMethod(string $identifier): PaymentMethodInterface
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $paymentMethod;
    }

    private function createPaymentMethodView(string $adminLabel): PaymentMethodViewInterface
    {
        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);
        $paymentMethodView->expects(self::any())
            ->method('getAdminLabel')
            ->willReturn($adminLabel);

        return $paymentMethodView;
    }
}
