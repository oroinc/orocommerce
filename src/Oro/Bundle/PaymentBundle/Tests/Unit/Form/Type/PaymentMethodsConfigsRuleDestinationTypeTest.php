<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormBuilderInterface;

class PaymentMethodsConfigsRuleDestinationTypeTest extends AddressFormExtensionTestCase
{
    private PaymentMethodsConfigsRuleDestinationType $formType;
    private AddressCountryAndRegionSubscriber $subscriber;

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
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $builder->expects(self::any())
            ->method('add')
            ->willReturn($builder);
        $builder->expects(self::once())
            ->method('get')
            ->willReturn($builder);
        $this->formType->buildForm($builder, []);
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(PaymentMethodsConfigsRuleDestinationType::class);
        $options = $form->getConfig()->getOptions();
        self::assertContainsEquals('data_class', $options);
        self::assertContainsEquals('region_route', $options);
        self::assertStringContainsString('oro_api_country_get_regions', $options['region_route']);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?PaymentMethodsConfigsRuleDestination $data)
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

    public function submitDataProvider(): array
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

    private function getDestination(
        string $countryCode,
        string $regionCode,
        array $postalCodes
    ): PaymentMethodsConfigsRuleDestination {
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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
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
