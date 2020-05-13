<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleDestinationType;
use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingMethodsConfigsRuleDestinationTypeTest extends AddressFormExtensionTestCase
{
    use EntityTrait;

    /** @var ShippingMethodsConfigsRuleDestinationType */
    protected $formType;

    /** @var AddressCountryAndRegionSubscriber */
    protected $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new AddressCountryAndRegionSubscriberStub();
        $this->formType = new ShippingMethodsConfigsRuleDestinationType($this->subscriber);
        parent::setUp();
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
        $form = $this->factory->create(ShippingMethodsConfigsRuleDestinationType::class);
        $options = $form->getConfig()->getOptions();
        static::assertContainsEquals('region_route', $options);
        static::assertStringContainsString('oro_api_country_get_regions', $options['region_route']);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param null|ShippingMethodsConfigsRuleDestination $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create(ShippingMethodsConfigsRuleDestinationType::class, $data);

        $this->assertEquals($data, $form->getData());

        $form->submit([
            'country' => 'CA',
            'region' => 'CA-QC',
            'postalCodes' => 'code3, code4',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var ShippingMethodsConfigsRuleDestination $actual */
        $actual = $form->getData();
        // first code not stripped, because form used model transformer that split string by comma
        // our extension applied on pre_submit, so all string stripped
        $expected = $this->getDestination('CA', 'CA-QC', ['code3', 'code4_stripped']);

        $this->assertInstanceOf(ShippingMethodsConfigsRuleDestination::class, $actual);
        $this->assertEquals($expected->getCountry(), $actual->getCountry());
        $this->assertEquals($expected->getRegion(), $actual->getRegion());

        $getNames = function (ShippingMethodsConfigsRuleDestinationPostalCode $code) {
            return $code->getName();
        };

        $this->assertEquals(
            $expected->getPostalCodes()->map($getNames)->getValues(),
            $actual->getPostalCodes()->map($getNames)->getValues()
        );
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty default form data' => [
                'data' => null
            ],
            'with default form data' => [
                'data' => $this->getDestination('US', 'US-AL', ['code1', 'code2'])
            ]
        ];
    }

    /**
     * @param string $countryCode
     * @param string $regionCode
     * @param array $postalCodes
     * @return ShippingMethodsConfigsRuleDestination
     */
    protected function getDestination($countryCode, $regionCode, array $postalCodes)
    {
        $country = new Country($countryCode);

        $region = new Region($regionCode);
        $region->setCountry($country);

        $destination = new ShippingMethodsConfigsRuleDestination();
        $destination->setCountry($country)
            ->setRegion($region);

        foreach ($postalCodes as $code) {
            $postalCode = new ShippingMethodsConfigsRuleDestinationPostalCode();
            $postalCode->setName($code);

            $destination->addPostalCode($postalCode);
        }

        return $destination;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        ShippingMethodsConfigsRuleDestinationType::class => $this->formType,
                    ],
                    []
                ),
                $this->getValidatorExtension(true)
            ]
        );
    }
}
