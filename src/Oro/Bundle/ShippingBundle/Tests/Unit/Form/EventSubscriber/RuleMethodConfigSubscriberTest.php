<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
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
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroupValidator;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;

class RuleMethodConfigSubscriberTest extends FormIntegrationTestCase
{
    /**
     * @var RuleMethodConfigCollectionSubscriberProxy
     */
    protected $ruleMethodConfigCollectionSubscriber;

    /**
     * @var RuleMethodTypeConfigCollectionSubscriberProxy
     */
    protected $ruleMethodTypeConfigCollectionSubscriber;

    /**
     * @var RuleMethodConfigSubscriberProxy
     */
    protected $subscriber;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    public function setUp()
    {
        $this->methodRegistry = new ShippingMethodRegistry();
        $this->ruleMethodTypeConfigCollectionSubscriber = new RuleMethodTypeConfigCollectionSubscriberProxy();
        $this->ruleMethodConfigCollectionSubscriber = new RuleMethodConfigCollectionSubscriberProxy();
        $this->subscriber = new RuleMethodConfigSubscriberProxy();
        parent::setUp();
        $this->ruleMethodTypeConfigCollectionSubscriber
            ->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
        $this->ruleMethodConfigCollectionSubscriber
            ->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
        $this->subscriber->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
    }

    public function test()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSet',
                FormEvents::PRE_SUBMIT => 'preSubmit'
            ],
            RuleMethodTypeConfigCollectionSubscriberProxy::getSubscribedEvents()
        );
    }

    public function testPreSet()
    {
        $flatRate = new FlatRateShippingMethodProvider();
        $this->methodRegistry->addProvider($flatRate);
        $form = $this->factory->create(ShippingRuleType::class);
        $shippingRule = new ShippingRule();
        $methodConfig = new ShippingRuleMethodConfig();
        $methodConfig->setMethod(FlatRateShippingMethod::IDENTIFIER);
        $shippingRule->addMethodConfig($methodConfig);
        $form->setData($shippingRule);
        $this->assertCount(1, $shippingRule->getMethodConfigs());
        $this->assertCount(1, $methodConfig->getTypeConfigs());
        $typeConfig = $methodConfig->getTypeConfigs()->first();
        $this->assertEquals(FlatRateShippingMethodType::IDENTIFIER, $typeConfig->getType());
        $this->assertEquals(
            FlatRateShippingMethodType::IDENTIFIER,
            $typeConfig->getType()
        );
        $children = $form->get('methodConfigs')->get(0)->get('typeConfigs')->all();
        $this->assertCount(1, $children);
        $configsForm = reset($children);
        /** @var ShippingRuleMethodTypeConfig $actualConfig */
        $actualConfig = $configsForm->getData();
        $this->assertEquals($typeConfig, $actualConfig);
        $this->assertEquals($typeConfig->getType(), $actualConfig->getType());
        $this->assertEquals($methodConfig, $actualConfig->getMethodConfig());
    }

    public function testPreSetWithData()
    {
        $flatRate = new FlatRateShippingMethodProvider();
        $this->methodRegistry->addProvider($flatRate);
        $form = $this->factory->create(ShippingRuleType::class);
        $shippingRule = new ShippingRule();
        $methodConfig = new ShippingRuleMethodConfig();
        $methodConfig->setMethod(FlatRateShippingMethod::IDENTIFIER);
        $typeConfig = new ShippingRuleMethodTypeConfig();
        $typeConfig->setType(FlatRateShippingMethodType::IDENTIFIER);
        $methodConfig->addTypeConfig($typeConfig);
        $shippingRule->addMethodConfig($methodConfig);
        $form->setData($shippingRule);
        $this->assertCount(1, $shippingRule->getMethodConfigs());
        $this->assertCount(1, $methodConfig->getTypeConfigs());
        $this->assertEquals(FlatRateShippingMethodType::IDENTIFIER, $typeConfig->getType());
        $children = $form->get('methodConfigs')->get(0)->get('typeConfigs')->all();
        $this->assertCount(1, $children);
        $configsForm = reset($children);
        /** @var ShippingRuleMethodTypeConfig $actualConfig */
        $actualConfig = $configsForm->getData();
        $this->assertEquals($typeConfig, $actualConfig);
        $this->assertEquals($typeConfig->getType(), $actualConfig->getType());
        $this->assertEquals($methodConfig, $actualConfig->getMethodConfig());
    }

    public function testPreSubmitWithData()
    {
        $flatRate = new FlatRateShippingMethodProvider();
        $this->methodRegistry->addProvider($flatRate);
        $form = $this->factory->create(ShippingRuleType::class);
        $shippingRule = new ShippingRule();

        $form->setData($shippingRule);
        $form->submit([
            'methodConfigs' => [
                [
                    'method' => FlatRateShippingMethod::IDENTIFIER,
                    'typeConfigs' => [
                        [
                            'type' => FlatRateShippingMethodType::IDENTIFIER,
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertCount(1, $shippingRule->getMethodConfigs());
        /** @var ShippingRuleMethodConfig $methodConfig */
        $methodConfig = $shippingRule->getMethodConfigs()->first();
        $this->assertCount(1, $methodConfig->getTypeConfigs());
        $typeConfig = $methodConfig->getTypeConfigs()->first();
        $this->assertEquals(FlatRateShippingMethodType::IDENTIFIER, $typeConfig->getType());
        $children = $form->get('methodConfigs')->get(0)->get('typeConfigs')->all();
        $this->assertCount(1, $children);
        $configsForm = reset($children);
        /** @var ShippingRuleMethodTypeConfig $actualConfig */
        $actualConfig = $configsForm->getData();
        $this->assertEquals($typeConfig, $actualConfig);
        $this->assertEquals($typeConfig->getType(), $actualConfig->getType());
        $this->assertEquals($methodConfig, $actualConfig->getMethodConfig());
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

        $translator = $this->getMock(TranslatorInterface::class);
        $translator->expects(static::any())
            ->method('trans')
            ->will(static::returnCallback(function ($message) {
                return $message.'_translated';
            }));

        return [
            new PreloadedExtension(
                [
                    ShippingRuleType::class => new ShippingRuleType($this->methodRegistry, $translator),
                    FlatRateShippingMethodTypeOptionsType::class
                    => new FlatRateShippingMethodTypeOptionsType($roundingService),
                    ShippingRuleMethodConfigCollectionType::class
                    => new ShippingRuleMethodConfigCollectionType($this->ruleMethodConfigCollectionSubscriber),
                    ShippingRuleMethodConfigType::class
                    => new ShippingRuleMethodConfigType($this->subscriber, $this->methodRegistry),
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
