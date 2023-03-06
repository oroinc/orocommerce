<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleDestinationType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingMethodsConfigsRuleDestinationTypeTest extends AddressFormExtensionTestCase
{
    private AddressCountryAndRegionSubscriber $subscriber;
    private ShippingMethodsConfigsRuleDestinationType $formType;

    protected function setUp(): void
    {
        $this->subscriber = new AddressCountryAndRegionSubscriberStub();
        $this->formType = new ShippingMethodsConfigsRuleDestinationType($this->subscriber);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(true)
        ]);
    }

    private function getDestination(
        string $countryCode,
        string $regionCode,
        array $postalCodes
    ): ShippingMethodsConfigsRuleDestination {
        $country = new Country($countryCode);

        $region = new Region($regionCode);
        $region->setCountry($country);

        $destination = new ShippingMethodsConfigsRuleDestination();
        $destination->setCountry($country);
        $destination->setRegion($region);

        foreach ($postalCodes as $code) {
            $postalCode = new ShippingMethodsConfigsRuleDestinationPostalCode();
            $postalCode->setName($code);

            $destination->addPostalCode($postalCode);
        }

        return $destination;
    }

    public function testBuildFormSubscriber()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $builder->expects(self::any())
            ->method('add')
            ->willReturn($builder);
        $builder->expects(self::any())
            ->method('get')
            ->willReturn($builder);
        $this->formType->buildForm($builder, []);
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(ShippingMethodsConfigsRuleDestinationType::class);
        $options = $form->getConfig()->getOptions();
        self::assertContainsEquals('region_route', $options);
        self::assertStringContainsString('oro_api_country_get_regions', $options['region_route']);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?ShippingMethodsConfigsRuleDestination $data)
    {
        $form = $this->factory->create(ShippingMethodsConfigsRuleDestinationType::class, $data);

        self::assertEquals($data, $form->getData());

        $form->submit([
            'country' => 'CA',
            'region' => 'CA-QC',
            'postalCodes' => 'code3, code4',
        ]);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        /** @var ShippingMethodsConfigsRuleDestination $actual */
        $actual = $form->getData();
        // first code not stripped, because form used model transformer that split string by comma
        // our extension applied on pre_submit, so all string stripped
        $expected = $this->getDestination('CA', 'CA-QC', ['code3', 'code4_stripped']);

        self::assertInstanceOf(ShippingMethodsConfigsRuleDestination::class, $actual);
        self::assertEquals($expected->getCountry(), $actual->getCountry());
        self::assertEquals($expected->getRegion(), $actual->getRegion());

        $getNames = function (ShippingMethodsConfigsRuleDestinationPostalCode $code) {
            return $code->getName();
        };

        self::assertEquals(
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

    public function testGetBlockPrefix()
    {
        self::assertEquals('oro_shipping_methods_configs_rule_destination', $this->formType->getBlockPrefix());
    }
}
