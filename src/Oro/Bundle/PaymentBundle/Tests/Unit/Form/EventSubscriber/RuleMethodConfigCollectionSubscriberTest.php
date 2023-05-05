<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleType;
use Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\ExpressionLanguageSyntaxValidator;

class RuleMethodConfigCollectionSubscriberTest extends FormIntegrationTestCase
{
    private const PAYMENT_TYPE = 'payment_type_mock';

    private RuleMethodConfigCollectionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new RuleMethodConfigCollectionSubscriber(
            $this->createMock(PaymentMethodProviderInterface::class)
        );
        parent::setUp();
    }

    public function test()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSet',
                FormEvents::PRE_SUBMIT => 'preSubmit'
            ],
            RuleMethodConfigCollectionSubscriber::getSubscribedEvents()
        );
    }

    public function testPreSet()
    {
        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class);
        $paymentRule = new PaymentMethodsConfigsRule();
        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType(self::PAYMENT_TYPE);
        $paymentRule->addMethodConfig($methodConfig);
        $form->setData($paymentRule);
        $this->assertCount(0, $form->get('methodConfigs'));
    }

    public function testPreSubmitWithData()
    {
        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class);
        $paymentRule = new PaymentMethodsConfigsRule();

        $form->submit([
            'methodConfigs' => [
                [
                    'type' => self::PAYMENT_TYPE
                ]
            ]
        ]);

        $this->assertCount(0, $paymentRule->getMethodConfigs());
        $this->assertCount(0, $form->get('methodConfigs'));
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD']);

        $translatableEntity = $this->getMockBuilder(TranslatableEntityType::class)
            ->onlyMethods(['configureOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMethodProvider = new CompositePaymentMethodProvider([]);
        $methodViewProvider = new CompositePaymentMethodViewProvider([]);

        return [
            new PreloadedExtension(
                [
                    PaymentMethodsConfigsRuleType::class => new PaymentMethodsConfigsRuleType(
                        $paymentMethodProvider,
                        $methodViewProvider
                    ),
                    PaymentMethodConfigCollectionType::class => new PaymentMethodConfigCollectionType(
                        $this->subscriber
                    ),
                    PaymentMethodConfigType::class => new PaymentMethodConfigType(
                        $paymentMethodProvider,
                        $methodViewProvider
                    ),
                    CurrencySelectionType::class => new CurrencySelectionType(
                        $currencyProvider,
                        $this->createMock(LocaleSettings::class),
                        $this->createMock(CurrencyNameHelper::class)
                    ),
                    CollectionType::class => new CollectionType(),
                    PaymentMethodsConfigsRuleDestinationType::class => new PaymentMethodsConfigsRuleDestinationType(
                        new AddressCountryAndRegionSubscriberStub()
                    ),
                    TranslatableEntityType::class => $translatableEntity,
                ],
                [FormType::class => [
                    new AdditionalAttrExtension(),
                    new StripTagsExtensionStub($this),
                ]]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        return [
            'oro_rule.validator_constraints.expression_language_syntax_validator' =>
                $this->createMock(ExpressionLanguageSyntaxValidator::class),
        ];
    }
}
