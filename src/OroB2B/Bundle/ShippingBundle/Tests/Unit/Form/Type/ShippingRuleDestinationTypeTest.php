<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleDestinationType;

class ShippingRuleDestinationTypeTest extends FormIntegrationTestCase
{
    /** @var ShippingRuleDestinationType */
    protected $formType;

    /** @var AddressCountryAndRegionSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        parent::setUp();
        $this->subscriber = new AddressCountryAndRegionSubscriberStub();
        $this->formType = new ShippingRuleDestinationType($this->subscriber);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingRuleDestinationType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildFormSubscriber()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $builder->expects($this->any())
            ->method('add')
            ->willReturn($builder);
        $this->formType->buildForm($builder, []);
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);
        $options = $form->getConfig()->getOptions();
        $this->assertContains('region_route', $options);
        $this->assertContains('oro_api_country_get_regions', $options['region_route']);
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
            'country' => 'US',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertEquals((new ShippingRuleDestination())->setCountry(new Country('US')), $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [null],
            [
                (new ShippingRuleDestination())->setCountry(new Country('AF'))
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
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

        return [
            new PreloadedExtension(
                [
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
