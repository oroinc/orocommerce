<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\PreloadedExtension;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleConfigurationCollectionType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleConfigurationType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleDestinationType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleType;
use OroB2B\Bundle\ShippingBundle\Method\FlatRateShippingMethod;
use OroB2B\Bundle\ShippingBundle\Form\EventSubscriber\RuleConfigurationSubscriber;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class RuleConfigurationSubscriberTest extends FormIntegrationTestCase
{
    /**
     * @var SubscriberProxy
     */
    protected $subscriber;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    public function setUp()
    {
        $this->methodRegistry = new ShippingMethodRegistry();
        $this->subscriber = new SubscriberProxy();
        parent::setUp();
        $this->subscriber->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
    }

    public function test()
    {
        $this->assertEquals(
            [FormEvents::PRE_SET_DATA => 'preSet'],
            RuleConfigurationSubscriber::getSubscribedEvents()
        );
    }

    public function testPreSet()
    {
        $flatRate = new FlatRateShippingMethod();
        $this->methodRegistry->addShippingMethod($flatRate);
        $form = $this->factory->create(new ShippingRuleType());
        $shippingRule = new ShippingRule();
        $form->setData($shippingRule);
        $this->assertCount(1, $shippingRule->getConfigurations());
        /** @var ShippingRuleConfiguration $config */
        $config = $shippingRule->getConfigurations()->first();
        $this->assertInstanceOf(FlatRateRuleConfiguration::class, $config);
        $this->assertEquals(FlatRateShippingMethod::NAME, $config->getMethod());
        $this->assertEquals(FlatRateShippingMethod::NAME, $config->getType());

        $children = $form->get('configurations')->all();
        $configsForm = reset($children);
        /** @var ShippingRuleConfiguration $actualConfig */
        $actualConfig = $configsForm->getData();
        $this->assertEquals($config, $actualConfig);
        $this->assertEquals($config->getMethod(), $actualConfig->getMethod());
        $this->assertEquals($config->getType(), $config->getType());
        $this->assertEquals($shippingRule, $config->getRule());
    }

    public function testPreSetWithData()
    {
        $flatRate = new FlatRateShippingMethod();
        $this->methodRegistry->addShippingMethod($flatRate);
        $form = $this->factory->create(new ShippingRuleType());
        $shippingRule = new ShippingRule();
        $config = new FlatRateRuleConfiguration();
        $config->setMethod(FlatRateShippingMethod::NAME)
            ->setType(FlatRateShippingMethod::NAME)
            ->setProcessingType(FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER)
            ->setPrice(Price::create(10, 'USD'));
        $shippingRule->addConfiguration($config);
        $form->setData($shippingRule);
        $this->assertCount(1, $shippingRule->getConfigurations());
        $this->assertSame($config, $shippingRule->getConfigurations()->first());

        $children = $form->get('configurations')->all();
        $configsForm = reset($children);
        $this->assertSame($config, $configsForm->getData());
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
                    ShippingRuleType::NAME => new ShippingRuleType(),
                    FlatRateShippingConfigurationType::NAME => new FlatRateShippingConfigurationType($roundingService),
                    ShippingRuleConfigurationCollectionType::NAME
                    => new ShippingRuleConfigurationCollectionType($this->subscriber),
                    ShippingRuleConfigurationType::NAME => new ShippingRuleConfigurationType(),
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
