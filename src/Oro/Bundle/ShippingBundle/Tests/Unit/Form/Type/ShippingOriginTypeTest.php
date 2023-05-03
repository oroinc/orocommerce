<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingOriginType;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingOriginTypeTest extends AddressFormExtensionTestCase
{
    private ShippingOriginType $formType;

    protected function setUp(): void
    {
        $this->formType = new ShippingOriginType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(ShippingOrigin::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => ShippingOrigin::class,
                'csrf_token_id' => 'shipping_origin'
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingOriginType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        array $submittedData,
        mixed $expectedData,
        mixed $defaultData = null,
        array $options = []
    ) {
        $form = $this->factory->create(ShippingOriginType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        return [
            'empty data' => [
                'submittedData' => [],
                'expectedData' => $this->getShippingOrigin(),
                'defaultData' => null,
            ],
            'full data' => [
                'submittedData' => [
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'postalCode' => 'code1',
                    'city' => 'city1',
                    'street' => 'street1',
                    'street2' => 'street2',
                ],
                'expectedData' => $this->getShippingOrigin(
                    self::COUNTRY_WITH_REGION,
                    true,
                    self::REGION_WITH_COUNTRY,
                    'code1_stripped',
                    'city1_stripped',
                    'street1_stripped',
                    'street2_stripped'
                ),
                'defaultData' => null,
            ],
            'full data with default' => [
                'submittedData' => [
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'postalCode' => 'code2',
                    'city' => 'city2',
                    'street' => 'street2',
                    'street2' => 'street3',
                ],
                'expectedData' => $this->getShippingOrigin(
                    self::COUNTRY_WITH_REGION,
                    true,
                    self::REGION_WITH_COUNTRY,
                    'code2_stripped',
                    'city2_stripped',
                    'street2_stripped',
                    'street3_stripped'
                ),
                'defaultData' => $this->getShippingOrigin(
                    self::COUNTRY_WITH_REGION,
                    true,
                    self::REGION_WITH_COUNTRY,
                    'code1',
                    'city1',
                    'street1',
                    'street2'
                ),
            ],
        ];
    }

    private function getShippingOrigin(
        string $countryCode = null,
        bool $linkRegionCountry = null,
        string $regionCode = null,
        string $postalCode = null,
        string $city = null,
        string $street = null,
        string $street2 = null
    ): ShippingOrigin {
        $shippingOrigin = new ShippingOrigin();

        if ($countryCode) {
            $country = new Country($countryCode);

            $shippingOrigin->setCountry($country);
        }

        if ($regionCode) {
            $region = new Region($regionCode);

            if ($linkRegionCountry) {
                $country->addRegion($region);
                $region->setCountry($country);
            }

            $shippingOrigin->setRegion($region);
        }

        if ($postalCode) {
            $shippingOrigin->setPostalCode($postalCode);
        }

        if ($city) {
            $shippingOrigin->setCity($city);
        }

        if ($street) {
            $shippingOrigin->setStreet($street);
        }

        if ($street2) {
            $shippingOrigin->setStreet2($street2);
        }

        return $shippingOrigin;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension(
                [
                    ShippingOriginType::class => $this->formType
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ]);
    }
}
