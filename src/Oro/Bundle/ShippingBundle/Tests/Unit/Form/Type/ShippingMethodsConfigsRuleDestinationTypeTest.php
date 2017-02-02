<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleDestinationType;
use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;

class ShippingMethodsConfigsRuleDestinationTypeTest extends AddressFormExtensionTestCase
{
    /** @var ShippingMethodsConfigsRuleDestinationType */
    protected $formType;

    /** @var AddressCountryAndRegionSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        parent::setUp();
        $this->subscriber = new AddressCountryAndRegionSubscriberStub();
        $this->formType = new ShippingMethodsConfigsRuleDestinationType($this->subscriber);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingMethodsConfigsRuleDestinationType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildFormSubscriber()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $builder->expects($this->any())
            ->method('add')
            ->willReturn($builder);
        $builder->expects($this->any())
            ->method('get')
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
        $this->assertEquals(
            (new ShippingMethodsConfigsRuleDestination())->setCountry(new Country('US')),
            $form->getData()
        );
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [null],
            [
                (new ShippingMethodsConfigsRuleDestination())->setCountry(new Country('AF'))
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'oro_country' => new CountryType(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'translatable_entity' => $this->getTranslatableEntity(),
                    'oro_region' => new RegionType(),
                ],
                ['form' => [new AdditionalAttrExtension()]]
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
