<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\TaxBundle\Form\Type\TaxBaseExclusionType;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxBaseExclusionTypeTest extends AbstractAddressTestCase
{
    /** @var TaxBaseExclusionType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new TaxBaseExclusionType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass('\ArrayObject');

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_tax_base_exclusion', $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertEquals('\ArrayObject', $options['data_class']);
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
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'option' => 'shipping_origin',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => null,
                    'option' => 'shipping_origin',
                ],
            ],
            'valid without region' => [
                'isValid' => true,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => null,
                    'option' => 'shipping_origin',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => null,
                    'region_text' => null,
                    'option' => 'shipping_origin',
                ],
            ],
            'invalid without country' => [
                'isValid' => false,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => null,
                    'region' => 'US-AL',
                    'option' => 'shipping_origin',
                ],
                'expectedData' => [
                    'country' => null,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => null,
                    'option' => 'shipping_origin',
                ],
            ],
            'invalid without option' => [
                'isValid' => false,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'option' => 'false',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => (new Region('US-AL'))->setCountry($country),
                    'region_text' => null,
                    'option' => null,
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
