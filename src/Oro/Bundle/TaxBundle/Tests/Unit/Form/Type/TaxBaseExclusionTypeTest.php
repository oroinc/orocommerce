<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Form\Type\TaxBaseExclusionType;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $this->assertEquals('oro_tax_base_exclusion', $this->formType->getName());
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
        list($country, $region) = $this->getValidCountryAndRegion();

        return [
            'valid form' => [
                'isValid' => true,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'option' => 'shipping_origin',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => $region,
                    'region_text' => null,
                    'option' => 'shipping_origin',
                ],
            ],
            'valid without region' => [
                'isValid' => true,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITHOUT_REGION,
                    'region' => null,
                    'option' => 'shipping_origin',
                ],
                'expectedData' => [
                    'country' => new Country(self::COUNTRY_WITHOUT_REGION),
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
                    'region' => self::REGION_WITH_COUNTRY,
                    'option' => 'shipping_origin',
                ],
                'expectedData' => [
                    'country' => null,
                    'region' => $region,
                    'region_text' => null,
                    'option' => 'shipping_origin',
                ],
            ],
            'invalid without option' => [
                'isValid' => false,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'option' => 'false',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => $region,
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
