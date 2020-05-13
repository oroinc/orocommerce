<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormBuilderInterface;

class PaymentMethodsConfigsRuleDestinationTypeTest extends AddressFormExtensionTestCase
{
    /** @var PaymentMethodsConfigsRuleDestinationType */
    protected $formType;

    /** @var AddressCountryAndRegionSubscriber */
    protected $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriber = new AddressCountryAndRegionSubscriberStub();
        $this->formType = new PaymentMethodsConfigsRuleDestinationType($this->subscriber);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PaymentMethodsConfigsRuleDestinationType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildFormSubscriber()
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
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
        $form = $this->factory->create(PaymentMethodsConfigsRuleDestinationType::class);
        $options = $form->getConfig()->getOptions();
        static::assertContainsEquals('data_class', $options);
        static::assertContainsEquals('region_route', $options);
        static::assertStringContainsString('oro_api_country_get_regions', $options['region_route']);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param null|PaymentMethodsConfigsRuleDestination $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create(PaymentMethodsConfigsRuleDestinationType::class, $data);

        $this->assertEquals($data, $form->getData());

        $form->submit([
            'country' => 'CA',
            'region' => 'CA-QC',
            'postalCodes' => 'code3, code4',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var PaymentMethodsConfigsRuleDestination $actual */
        $actual = $form->getData();
        // first code not stripped, because form used model transformer that split string by comma
        // our extension applied on pre_submit, so all string stripped
        $expected = $this->getDestination('CA', 'CA-QC', ['code3', 'code4_stripped']);

        $this->assertInstanceOf(PaymentMethodsConfigsRuleDestination::class, $actual);
        $this->assertEquals($expected->getCountry(), $actual->getCountry());
        $this->assertEquals($expected->getRegion(), $actual->getRegion());

        $getNames = function (PaymentMethodsConfigsRuleDestinationPostalCode $code) {
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
     * @return PaymentMethodsConfigsRuleDestination
     */
    protected function getDestination($countryCode, $regionCode, array $postalCodes)
    {
        $country = new Country($countryCode);

        $region = new Region($regionCode);
        $region->setCountry($country);

        $destination = new PaymentMethodsConfigsRuleDestination();
        $destination->setCountry($country)
            ->setRegion($region);

        foreach ($postalCodes as $code) {
            $postalCode = new PaymentMethodsConfigsRuleDestinationPostalCode();
            $postalCode->setName($code);

            $destination->addPostalCode($postalCode);
        }

        return $destination;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([$this->formType], []),
                $this->getValidatorExtension(true)
            ]
        );
    }
}
