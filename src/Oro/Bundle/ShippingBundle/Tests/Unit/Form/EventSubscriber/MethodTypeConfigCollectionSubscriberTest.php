<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingMethodTypeOptionsType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigCollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleDestinationType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodTypeConfigCollectionType;
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

class MethodTypeConfigCollectionSubscriberTest extends FormIntegrationTestCase
{
    /**
     * @var MethodTypeConfigCollectionSubscriberProxy
     */
    protected $subscriber;

    /**
     * @var MethodConfigSubscriberProxy
     */
    protected $methodConfigSubscriber;

    /**
     * @var MethodConfigCollectionSubscriberProxy
     */
    protected $methodConfigCollectionSubscriber;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    public function setUp()
    {
        $this->methodRegistry = new ShippingMethodRegistry();
        $this->subscriber = new MethodTypeConfigCollectionSubscriberProxy();
        $this->methodConfigSubscriber = new MethodConfigSubscriberProxy();
        $this->methodConfigCollectionSubscriber = new MethodConfigCollectionSubscriberProxy();
        parent::setUp();
        $this->subscriber->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
        $this->methodConfigSubscriber->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
        $this->methodConfigCollectionSubscriber
            ->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
    }

    public function test()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSet',
                FormEvents::PRE_SUBMIT => 'preSubmit'
            ],
            MethodTypeConfigCollectionSubscriberProxy::getSubscribedEvents()
        );
    }

    public function testPreSet()
    {
        $flatRate = new FlatRateShippingMethodProvider();
        $this->methodRegistry->addProvider($flatRate);
        $form = $this->factory->create(ShippingMethodsConfigsRuleType::class);
        $shippingRule = new ShippingMethodsConfigsRule();
        $methodConfig = new ShippingMethodConfig();
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
        /** @var ShippingMethodTypeConfig $actualConfig */
        $actualConfig = $configsForm->getData();
        $this->assertEquals($typeConfig, $actualConfig);
        $this->assertEquals($typeConfig->getType(), $actualConfig->getType());
        $this->assertEquals($methodConfig, $actualConfig->getMethodConfig());
    }

    public function testPreSetWithData()
    {
        $flatRate = new FlatRateShippingMethodProvider();
        $this->methodRegistry->addProvider($flatRate);
        $form = $this->factory->create(ShippingMethodsConfigsRuleType::class);
        $shippingRule = new ShippingMethodsConfigsRule();
        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod(FlatRateShippingMethod::IDENTIFIER);
        $typeConfig = new ShippingMethodTypeConfig();
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
        /** @var ShippingMethodTypeConfig $actualConfig */
        $actualConfig = $configsForm->getData();
        $this->assertEquals($typeConfig, $actualConfig);
        $this->assertEquals($typeConfig->getType(), $actualConfig->getType());
        $this->assertEquals($methodConfig, $actualConfig->getMethodConfig());
    }

    public function testPreSubmitWithData()
    {
        $flatRate = new FlatRateShippingMethodProvider();
        $this->methodRegistry->addProvider($flatRate);
        $form = $this->factory->create(ShippingMethodsConfigsRuleType::class);
        $shippingRule = new ShippingMethodsConfigsRule();

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
        /** @var ShippingMethodConfig $methodConfig */
        $methodConfig = $shippingRule->getMethodConfigs()->first();
        $this->assertCount(1, $methodConfig->getTypeConfigs());
        $typeConfig = $methodConfig->getTypeConfigs()->first();
        $this->assertEquals(FlatRateShippingMethodType::IDENTIFIER, $typeConfig->getType());
        $children = $form->get('methodConfigs')->get(0)->get('typeConfigs')->all();
        $this->assertCount(1, $children);
        $configsForm = reset($children);
        /** @var ShippingMethodTypeConfig $actualConfig */
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

        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
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
                    ShippingMethodsConfigsRuleType::class
                    => new ShippingMethodsConfigsRuleType($this->methodRegistry, $translator),
                    FlatRateShippingMethodTypeOptionsType::class
                    => new FlatRateShippingMethodTypeOptionsType($roundingService),
                    ShippingMethodConfigCollectionType::class
                    => new ShippingMethodConfigCollectionType($this->methodConfigCollectionSubscriber),
                    ShippingMethodConfigType::class
                    => new ShippingMethodConfigType($this->methodConfigSubscriber, $this->methodRegistry),
                    ShippingMethodTypeConfigCollectionType::class =>
                        new ShippingMethodTypeConfigCollectionType($this->subscriber),
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $currencyProvider,
                        $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock(),
                        $this->getMockBuilder(CurrencyNameHelper::class)->disableOriginalConstructor()->getMock()
                    ),
                    CollectionType::NAME => new CollectionType(),
                    ShippingMethodsConfigsRuleDestinationType::NAME => new ShippingMethodsConfigsRuleDestinationType(
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
