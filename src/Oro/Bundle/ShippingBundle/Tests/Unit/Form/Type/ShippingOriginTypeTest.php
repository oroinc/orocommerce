<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingOriginType;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingOriginTypeTest extends AddressFormExtensionTestCase
{
    const DATA_CLASS = 'Oro\Bundle\ShippingBundle\Model\ShippingOrigin';

    /**
     * @var ShippingOriginType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formType = new ShippingOriginType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => self::DATA_CLASS,
                'csrf_token_id' => 'shipping_origin'
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingOriginType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $options
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null, $options = [])
    {
        $form = $this->factory->create(ShippingOriginType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
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

    /**
     * @param string $countryCode
     * @param boolean $linkRegionCountry
     * @param string $regionCode
     * @param string $postalCode
     * @param string $city
     * @param string $street
     * @param string $street2
     * @return ShippingOrigin
     */
    protected function getShippingOrigin(
        $countryCode = null,
        $linkRegionCountry = null,
        $regionCode = null,
        $postalCode = null,
        $city = null,
        $street = null,
        $street2 = null
    ) {
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
     * {@inheritdoc}
     */
    protected function getExtensions()
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
