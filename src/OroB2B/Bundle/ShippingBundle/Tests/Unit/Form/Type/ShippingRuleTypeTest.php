<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\PreloadedExtension;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleConfigurationCollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleDestinationType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleType;
use Oro\Bundle\ShippingBundle\Method\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber\SubscriberProxy;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledConfigurationValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledConfigurationValidationGroupValidator;
use Oro\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleConfigurationType;

class ShippingRuleTypeTest extends FormIntegrationTestCase
{
    /** @var ShippingRuleType */
    protected $formType;

    /**
     * @var SubscriberProxy
     */
    protected $subscriber;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    protected function setUp()
    {
        $this->methodRegistry = new ShippingMethodRegistry();
        $flatRate = new FlatRateShippingMethod();
        $this->methodRegistry->addShippingMethod($flatRate);
        $this->subscriber = new SubscriberProxy();
        parent::setUp();
        $this->subscriber->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
        parent::setUp();
        $this->formType = new ShippingRuleType();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingRuleType::NAME, $this->formType->getBlockPrefix());
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
            'name' => 'new rule',
            'currency' => 'USD',
            'priority' => '1',
            'configurations' => [
                [
                    'enabled' => true,
                    'method' => FlatRateShippingMethod::NAME,
                    'type' => FlatRateShippingMethod::NAME,
                    'processingType' => FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER,
                    'value' => 21,
                ]
            ],
        ]);

        $shippingRule = (new ShippingRule())
            ->setName('new rule')
            ->setCurrency('USD')
            ->setPriority(1)
            ->setEnabled(false)
            ->addConfiguration(
                (new FlatRateRuleConfiguration())
                    ->setMethod(FlatRateShippingMethod::NAME)
                    ->setType(FlatRateShippingMethod::NAME)
                    ->setProcessingType(FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER)
                    ->setValue(21)
                    ->setEnabled(true)
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
            [(new ShippingRule())],
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
        $constraint = new EnabledConfigurationValidationGroup();

        return [
            $constraint->validatedBy() => new EnabledConfigurationValidationGroupValidator(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $configManager->expects($this->any())
            ->method('get')
            ->willReturn(['USD']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $country = new Country('US');
        $choices = [
            'OroAddressBundle:Country' => ['US' => $country],
            'OroAddressBundle:Region' => ['US-AL' => (new Region('US-AL'))->setCountry($country)],
        ];

        $translatableEntity->expects($this->any())->method('setDefaultOptions')->will(
            $this->returnCallback(
                function (OptionsResolver $resolver) use ($choices) {
                    $choiceList = function (Options $options) use ($choices) {
                        $className = $options->offsetGet('class');
                        if (array_key_exists($className, $choices)) {
                            return new ArrayChoiceList(
                                $choices[$className],
                                function ($item) {
                                    if ($item instanceof Country) {
                                        return $item->getIso2Code();
                                    }

                                    if ($item instanceof Region) {
                                        return $item->getCombinedCode();
                                    }

                                    return $item . uniqid('form', true);
                                }
                            );
                        }

                        return new ArrayChoiceList([]);
                    };

                    $resolver->setDefault('choice_list', $choiceList);
                }
            )
        );

        $roundingService = $this->getMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        return [
            new PreloadedExtension(
                [
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $configManager,
                        $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock()
                    ),
                    ShippingRuleConfigurationCollectionType::NAME => new ShippingRuleConfigurationCollectionType(
                        $this->subscriber
                    ),
                    FlatRateShippingConfigurationType::NAME => new FlatRateShippingConfigurationType($roundingService),
                    ShippingRuleConfigurationType::NAME => new ShippingRuleConfigurationType(),
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
