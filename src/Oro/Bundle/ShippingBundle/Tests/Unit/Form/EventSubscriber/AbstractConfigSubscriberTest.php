<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntax;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodTypeConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigCollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleDestinationType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodSelectType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodTypeConfigCollectionType;
use Oro\Bundle\ShippingBundle\Method\CompositeShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroupValidator;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnable;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnableValidator;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\ExpressionLanguageSyntaxValidator;

abstract class AbstractConfigSubscriberTest extends FormIntegrationTestCase
{
    protected EventSubscriberInterface $subscriber;
    protected ShippingMethodProviderInterface $shippingMethodProvider;
    protected MethodConfigSubscriberProxy $methodConfigSubscriber;
    protected MethodConfigCollectionSubscriber $methodConfigCollectionSubscriber;
    protected MethodTypeConfigCollectionSubscriber $methodTypeConfigCollectionSubscriber;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = new CompositeShippingMethodProvider([]);
        $this->methodConfigSubscriber = new MethodConfigSubscriberProxy();
        $this->methodConfigCollectionSubscriber = new MethodConfigCollectionSubscriber($this->shippingMethodProvider);
        $this->methodTypeConfigCollectionSubscriber = new MethodTypeConfigCollectionSubscriber(
            $this->shippingMethodProvider
        );

        parent::setUp();

        $this->methodConfigSubscriber->setFactory($this->factory);
        $this->methodConfigSubscriber->setShippingMethodProvider($this->shippingMethodProvider);
    }

    public function test()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSet',
                FormEvents::PRE_SUBMIT => 'preSubmit'
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testPreSet()
    {
        $form = $this->factory->create(ShippingMethodsConfigsRuleType::class);
        $shippingRule = new ShippingMethodsConfigsRule();
        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod('flat_rate');
        $shippingRule->addMethodConfig($methodConfig);
        $form->setData($shippingRule);
        $this->assertCount(0, $form->get('methodConfigs'));
    }

    public function testPreSubmitWithData()
    {
        $form = $this->factory->create(ShippingMethodsConfigsRuleType::class);
        $shippingRule = new ShippingMethodsConfigsRule();

        $form->submit([
            'methodConfigs' => [
                [
                    'method' => 'flat_rate',
                    'typeConfigs' => [
                        [
                            'type' => 'primary',
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertCount(0, $shippingRule->getMethodConfigs());
        $this->assertCount(0, $form->get('methodConfigs'));
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        $enabledTypeConfigsValidationGroup = new EnabledTypeConfigsValidationGroup();
        $shippingRuleEnable = new ShippingRuleEnable();
        $expressionLanguageSyntax = new ExpressionLanguageSyntax();

        return [
            $enabledTypeConfigsValidationGroup->validatedBy() => new EnabledTypeConfigsValidationGroupValidator(),
            $shippingRuleEnable->validatedBy() => $this->createMock(ShippingRuleEnableValidator::class),
            $expressionLanguageSyntax->validatedBy() => $this->createMock(ExpressionLanguageSyntaxValidator::class),
        ];
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

        $choicesProvider = $this->createMock(ShippingMethodChoicesProvider::class);
        $choicesProvider->expects($this->any())
            ->method('getMethods')
            ->willReturn([]);

        return [
            new PreloadedExtension(
                [
                    ShippingMethodsConfigsRuleType::class => new ShippingMethodsConfigsRuleType(),
                    ShippingMethodConfigCollectionType::class => new ShippingMethodConfigCollectionType(
                        $this->methodConfigCollectionSubscriber
                    ),
                    ShippingMethodConfigType::class => new ShippingMethodConfigType(
                        $this->methodConfigSubscriber,
                        $this->shippingMethodProvider
                    ),
                    ShippingMethodTypeConfigCollectionType::class => new ShippingMethodTypeConfigCollectionType(
                        $this->methodTypeConfigCollectionSubscriber
                    ),
                    CurrencySelectionType::class => new CurrencySelectionType(
                        $currencyProvider,
                        $this->createMock(LocaleSettings::class),
                        $this->createMock(CurrencyNameHelper::class)
                    ),
                    CollectionType::class => new CollectionType(),
                    ShippingMethodsConfigsRuleDestinationType::class => new ShippingMethodsConfigsRuleDestinationType(
                        new AddressCountryAndRegionSubscriberStub()
                    ),
                    OroChoiceType::class => new OroChoiceType(),
                    ShippingMethodSelectType::class => new ShippingMethodSelectType(
                        $choicesProvider,
                        $this->createMock(ShippingMethodIconProviderInterface::class),
                        $this->createMock(AssetHelper::class)
                    ),
                    TranslatableEntityType::class => $this->getMockBuilder(TranslatableEntityType::class)
                        ->onlyMethods(['configureOptions', 'buildForm'])
                        ->disableOriginalConstructor()
                        ->getMock()
                ],
                [FormType::class => [
                    new AdditionalAttrExtension(),
                    new StripTagsExtensionStub($this)
                ]]
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
