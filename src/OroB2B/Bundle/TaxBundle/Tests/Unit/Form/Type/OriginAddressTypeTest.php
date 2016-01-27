<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;

use OroB2B\Bundle\TaxBundle\Form\Type\OriginAddressType;
use OroB2B\Bundle\TaxBundle\Model\Address;

class OriginAddressTypeTest extends AbstractAddressTestCase
{
    /** @var OriginAddressType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new OriginAddressType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass('OroB2B\Bundle\TaxBundle\Model\Address');

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_tax_origin_address', $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertEquals('OroB2B\Bundle\TaxBundle\Model\Address', $options['data_class']);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $country = new Country('US');

        return [
            'valid form' => [
                'isValid' => true,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
            ],
            'invalid without region' => [
                'isValid' => false,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => null,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => null,
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
            ],
            'invalid without country' => [
                'isValid' => false,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => null,
                    'region' => 'US-AL',
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
                'expectedData' => [
                    'country' => null,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                ],
            ],
            'invalid without postal code' => [
                'isValid' => false,
                'defaultData' => new Address(),
                'viewData' => new Address(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'region_text' => 'Alabama',
                    'postal_code' => null,
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => 'Alabama',
                    'postal_code' => null,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return $this->formType;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge([$this->getValidatorExtension(true)], parent::getExtensions());
    }
}
