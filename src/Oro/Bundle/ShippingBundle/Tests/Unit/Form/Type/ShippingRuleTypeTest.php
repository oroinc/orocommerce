<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingMethodTypeOptionsType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleDestinationType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodConfigCollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodConfigType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodTypeConfigCollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleType;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber\RuleMethodConfigCollectionSubscriberProxy;
use Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber\RuleMethodConfigSubscriberProxy;
use Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber\RuleMethodTypeConfigCollectionSubscriberProxy;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroupValidator;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;

class ShippingRuleTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ShippingRuleType
     */
    protected $formType;

    /**
     * @var RuleMethodTypeConfigCollectionSubscriberProxy
     */
    protected $ruleMethodTypeConfigCollectionSubscriber;

    /**
     * @var RuleMethodConfigCollectionSubscriberProxy
     */
    protected $ruleMethodConfigCollectionSubscriber;

    /**
     * @var RuleMethodConfigSubscriberProxy
     */
    protected $ruleMethodConfigSubscriber;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    protected function setUp()
    {
        $this->methodRegistry = new ShippingMethodRegistry();
        $flatRate = new FlatRateShippingMethodProvider();
        $this->methodRegistry->addProvider($flatRate);
        $this->ruleMethodTypeConfigCollectionSubscriber = new RuleMethodTypeConfigCollectionSubscriberProxy();
        $this->ruleMethodConfigSubscriber = new RuleMethodConfigSubscriberProxy();
        $this->ruleMethodConfigCollectionSubscriber = new RuleMethodConfigCollectionSubscriberProxy();
        parent::setUp();
        $this->ruleMethodTypeConfigCollectionSubscriber
            ->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
        $this->ruleMethodConfigSubscriber->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
        $this->ruleMethodConfigCollectionSubscriber
            ->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);

        $translator = $this->getMock(TranslatorInterface::class);
        $translator->expects(static::any())
            ->method('trans')
            ->will(static::returnCallback(function ($message) {
                return $message.'_translated';
            }));

        $this->formType = new ShippingRuleType($this->methodRegistry, $translator);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingRuleType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|null $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create($this->formType, $data);

        $this->assertSame($data, $form->getData());

        $form->submit([
            'name' => 'new rule',
            'currency' => 'USD',
            'priority' => '1',
            'methodConfigs' => [
                [
                    'method' => FlatRateShippingMethod::IDENTIFIER,
                    'options' => [],
                    'typeConfigs' => [
                        [
                            'enabled' => '1',
                            'type' => FlatRateShippingMethodType::IDENTIFIER,
                            'options' => [
                                'price' => 12,
                                'type' => FlatRateShippingMethodType::PER_ITEM_TYPE,
                            ],
                        ]
                    ]
                ]
            ]
        ]);

        $shippingRule = (new ShippingRule())
            ->setName('new rule')
            ->setCurrency('USD')
            ->setPriority(1)
            ->setEnabled(false)
            ->addMethodConfig(
                (new ShippingRuleMethodConfig())
                    ->setMethod(FlatRateShippingMethod::IDENTIFIER)
                    ->setOptions([])
                    ->addTypeConfig(
                        (new ShippingRuleMethodTypeConfig())
                            ->setEnabled(true)
                            ->setType(FlatRateShippingMethodType::IDENTIFIER)
                            ->setOptions([
                                'price' => 12,
                                'handling_fee' => null,
                                'type' => FlatRateShippingMethodType::PER_ITEM_TYPE,
                            ])
                    )
            );

        $this->assertTrue($form->isValid());
        $this->assertEquals($shippingRule, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [new ShippingRule()],
            [
                (new ShippingRule())
                    ->setCurrency('EUR')
                    ->setName('old name')
                    ->setPriority(0)
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $constraint = new EnabledTypeConfigsValidationGroup();

        return [
            $constraint->validatedBy() => new EnabledTypeConfigsValidationGroupValidator(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        $roundingService = $this->getMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $configManager->expects($this->any())
            ->method('get')
            ->willReturn(['USD']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    FlatRateShippingMethodTypeOptionsType::class
                    => new FlatRateShippingMethodTypeOptionsType($roundingService),
                    ShippingRuleMethodConfigCollectionType::class
                    => new ShippingRuleMethodConfigCollectionType($this->ruleMethodConfigCollectionSubscriber),
                    ShippingRuleMethodConfigType::class
                    => new ShippingRuleMethodConfigType($this->ruleMethodConfigSubscriber, $this->methodRegistry),
                    ShippingRuleMethodTypeConfigCollectionType::class =>
                        new ShippingRuleMethodTypeConfigCollectionType($this->ruleMethodTypeConfigCollectionSubscriber),
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $configManager,
                        $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock()
                    ),
                    CollectionType::NAME => new CollectionType(),
                    ShippingRuleDestinationType::NAME => new ShippingRuleDestinationType(
                        new AddressCountryAndRegionSubscriberStub()
                    ),
                    'oro_country' => new CountryType(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'translatable_entity' => $translatableEntity,
                    'oro_region' => new RegionType(),
                ],
                ['form' => [new AdditionalAttrExtension()]]
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
