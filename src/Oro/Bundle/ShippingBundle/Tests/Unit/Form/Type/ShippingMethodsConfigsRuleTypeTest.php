<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntax;
use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntaxValidator;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigCollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleDestinationType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodSelectType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodTypeConfigCollectionType;
use Oro\Bundle\ShippingBundle\Method\CompositeShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber\MethodConfigCollectionSubscriberProxy;
use Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber\MethodConfigSubscriberProxy;
use Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber\MethodTypeConfigCollectionSubscriberProxy;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodProviderStub;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroupValidator;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnable;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnableValidator;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingMethodsConfigsRuleTypeTest extends FormIntegrationTestCase
{
    /**
     * @var MethodTypeConfigCollectionSubscriberProxy
     */
    protected $methodTypeConfigCollectionSubscriber;

    /**
     * @var MethodConfigCollectionSubscriberProxy
     */
    protected $methodConfigCollectionSubscriber;

    /**
     * @var MethodConfigSubscriberProxy
     */
    protected $methodConfigSubscriber;

    /**
     * @var CompositeShippingMethodProvider
     */
    protected $shippingMethodProvider;

    /**
     * @var ShippingMethodChoicesProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $choicesProvider;

    /**
     * @var ShippingMethodIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $iconProvider;

    /**
     * @var AssetHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $assetHelper;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = new CompositeShippingMethodProvider([new ShippingMethodProviderStub()]);
        $this->methodTypeConfigCollectionSubscriber = new MethodTypeConfigCollectionSubscriberProxy();
        $this->methodConfigSubscriber = new MethodConfigSubscriberProxy();
        $this->methodConfigCollectionSubscriber = new MethodConfigCollectionSubscriberProxy();

        $this->choicesProvider = $this->createMock(ShippingMethodChoicesProviderInterface::class);
        $this->choicesProvider->expects($this->any())
            ->method('getMethods')
            ->willReturn([]);
        $this->iconProvider = $this->createMock(ShippingMethodIconProviderInterface::class);
        $this->assetHelper = $this->createMock(AssetHelper::class);

        parent::setUp();

        $this->methodTypeConfigCollectionSubscriber
            ->setFactory($this->factory)->setMethodRegistry($this->shippingMethodProvider);
        $this->methodConfigSubscriber->setFactory($this->factory)->setMethodRegistry($this->shippingMethodProvider);
        $this->methodConfigCollectionSubscriber
            ->setFactory($this->factory)->setMethodRegistry($this->shippingMethodProvider);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(static::any())
            ->method('trans')
            ->will(static::returnCallback(function ($message) {
                return $message.'_translated';
            }));
    }

    public function testGetBlockPrefix()
    {
        $formType = new ShippingMethodsConfigsRuleType();
        $this->assertEquals(ShippingMethodsConfigsRuleType::BLOCK_PREFIX, $formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|null $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create(ShippingMethodsConfigsRuleType::class, $data);

        $this->assertSame($data, $form->getData());

        $form->submit([
            'rule' => [
                'name' => 'new rule',
                'sortOrder' => '1',
                'enabled' => false
            ],
            'currency' => 'USD',
            'methodConfigs' => [
                [
                    'method' => ShippingMethodProviderStub::METHOD_IDENTIFIER,
                    'options' => ['option' => 1],
                    'typeConfigs' => [
                        [
                            'enabled' => true,
                            'type' => ShippingMethodProviderStub::METHOD_TYPE_IDENTIFIER,
                            'options' => [
                                'price' => 12,
                                'type' => 'per_item',
                                'handling_fee' => 100
                            ],
                        ]
                    ]
                ]
            ]
        ]);

        $shippingRule = (new ShippingMethodsConfigsRule())
            ->setRule(
                (new Rule())
                    ->setName('new rule')
                    ->setSortOrder(1)
                    ->setEnabled(false)
            )
            ->setCurrency('USD')
            ->addMethodConfig(
                (new ShippingMethodConfig())
                    ->setMethod(ShippingMethodProviderStub::METHOD_IDENTIFIER)
                    ->setOptions(['option' => 1])
                    ->addTypeConfig(
                        (new ShippingMethodTypeConfig())
                            ->setEnabled(true)
                            ->setType(ShippingMethodProviderStub::METHOD_TYPE_IDENTIFIER)
                            ->setOptions([
                                'price' => 12,
                                'type' => 'per_item',
                                'handling_fee' => 100
                            ])
                    )
            );

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($shippingRule, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [new ShippingMethodsConfigsRule()],
            [
                (new ShippingMethodsConfigsRule())
                    ->setRule(
                        (new Rule())
                            ->setName('old name')
                            ->setSortOrder(0)
                            ->setEnabled(false)
                    )
                    ->setCurrency('EUR')
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
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
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD']);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['configureOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    ShippingMethodConfigCollectionType::class
                    => new ShippingMethodConfigCollectionType($this->methodConfigCollectionSubscriber),
                    ShippingMethodConfigType::class
                    => new ShippingMethodConfigType($this->methodConfigSubscriber, $this->shippingMethodProvider),
                    ShippingMethodTypeConfigCollectionType::class =>
                        new ShippingMethodTypeConfigCollectionType($this->methodTypeConfigCollectionSubscriber),
                    CurrencySelectionType::class => new CurrencySelectionType(
                        $currencyProvider,
                        $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock(),
                        $this->getMockBuilder(CurrencyNameHelper::class)->disableOriginalConstructor()->getMock()
                    ),
                    ShippingMethodsConfigsRuleDestinationType::class => new ShippingMethodsConfigsRuleDestinationType(
                        new AddressCountryAndRegionSubscriberStub()
                    ),
                    ShippingMethodSelectType::class => new ShippingMethodSelectType(
                        $this->choicesProvider,
                        $this->iconProvider,
                        $this->assetHelper
                    ),
                    TranslatableEntityType::class => $translatableEntity
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
