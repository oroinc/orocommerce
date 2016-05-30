<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;

use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginType;

class ShippingOriginTypeTest extends AddressFormExtensionTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin';

    /**
     * @var ShippingOriginType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ShippingOriginType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => self::DATA_CLASS,
                'intention' => 'shipping_origin'
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingOriginType::NAME, $this->formType->getName());
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
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
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
                    'country' => 'US',
                    'region' => 'US-AL',
                    'postalCode' => 'code1',
                    'city' => 'city1',
                    'street' => 'street1',
                    'street2' => 'street2',
                ],
                'expectedData' => $this->getShippingOrigin('US', 'US', 'US-AL', 'code1', 'city1', 'street1', 'street2'),
                'defaultData' => null,
            ],
            'full data with default' => [
                'submittedData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'postalCode' => 'code2',
                    'city' => 'city2',
                    'street' => 'street2',
                    'street2' => 'street3',
                ],
                'expectedData' => $this->getShippingOrigin('US', 'US', 'US-AL', 'code2', 'city2', 'street2', 'street3'),
                'defaultData' => $this->getShippingOrigin('US', 'US', 'US-AL', 'code1', 'city1', 'street1', 'street2'),
            ],
        ];
    }

    /**
     * @param string $countryCode
     * @param string $regionCountryCode
     * @param string $regionCode
     * @param string $postalCode
     * @param string $city
     * @param string $street
     * @param string $street2
     * @return ShippingOrigin
     */
    protected function getShippingOrigin(
        $countryCode = null,
        $regionCountryCode = null,
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
            if ($regionCountryCode) {
                $region->setCountry(new Country($regionCountryCode));
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
    public function getExtensions()
    {
        return array_merge(parent::getExtensions(), [$this->getValidatorExtension(true)]);
    }
}
