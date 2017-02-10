<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;

class PaymentMethodsConfigsRuleDestinationTypeTest extends AbstractPaymentMethodsConfigRuleTypeTest
{
    /** @var PaymentMethodsConfigsRuleDestinationType */
    protected $formType;

    /** @var AddressCountryAndRegionSubscriber */
    protected $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->subscriber = new AddressCountryAndRegionSubscriberStub();
        $this->formType = new PaymentMethodsConfigsRuleDestinationType($this->subscriber);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PaymentMethodsConfigsRuleDestinationType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildFormSubscriber()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMockBuilder(FormBuilderInterface::class)->getMock();
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $builder->expects(static::any())
            ->method('add')
            ->willReturn($builder);
        $builder->expects(static::once())
            ->method('get')
            ->willReturn($builder);
        $this->formType->buildForm($builder, []);
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);
        $options = $form->getConfig()->getOptions();
        $this->assertContains('data_class', $options);
        $this->assertContains('region_route', $options);
        $this->assertContains('oro_api_country_get_regions', $options['region_route']);
    }

    public function testSubmitNull()
    {
        $destination = null;

        $form = $this->factory->create($this->formType, $destination);

        $this->assertEquals($destination, $form->getData());

        $form->submit([
            'country' => 'US',
            'region' => 'US-AL',
            'postalCodes' => 'code1, code2',
        ]);

        $destination = (new PaymentMethodsConfigsRuleDestination())
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-AL'))
            ->addPostalCode((new PaymentMethodsConfigsRuleDestinationPostalCode())->setName('code1'))
            ->addPostalCode((new PaymentMethodsConfigsRuleDestinationPostalCode())->setName('code2'));
        $this->assertTrue($form->isValid());
        $this->assertEquals(
            $destination,
            $form->getData()
        );
    }

    public function testSubmit()
    {
        $destination = (new PaymentMethodsConfigsRuleDestination())
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-AL'))
            ->addPostalCode((new PaymentMethodsConfigsRuleDestinationPostalCode())->setName('code1'))
            ->addPostalCode((new PaymentMethodsConfigsRuleDestinationPostalCode())->setName('code2'));

        $form = $this->factory->create($this->formType, $destination);

        $this->assertEquals($destination, $form->getData());

        $form->submit([
            'country' => 'US',
            'region' => 'US-AL',
            'postalCodes' => 'code1, code2',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            $destination,
            $form->getData()
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        $translatableEntity = $this->getTranslatableEntity();

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
