<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\TaxBundle\Form\Type\TaxBaseExclusionType;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxBaseExclusionTypeTest extends AbstractAddressTestCase
{
    private TaxBaseExclusionType $formType;

    protected function setUp(): void
    {
        $this->formType = new TaxBaseExclusionType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(\ArrayObject::class);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertEquals(\ArrayObject::class, $options['data_class']);
    }

    /**
     * {@inheritDoc}
     */
    public function submitDataProvider(): array
    {
        [$country, $region] = $this->getValidCountryAndRegion();

        return [
            'valid form' => [
                'isValid' => true,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'option' => 'origin',
                ],
                'expectedData' => [
                    'country' => $country,
                    'region' => $region,
                    'region_text' => null,
                    'option' => 'origin',
                ],
            ],
            'valid without region' => [
                'isValid' => true,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => self::COUNTRY_WITHOUT_REGION,
                    'region' => null,
                    'option' => 'origin',
                ],
                'expectedData' => [
                    'country' => new Country(self::COUNTRY_WITHOUT_REGION),
                    'region' => null,
                    'region_text' => null,
                    'option' => 'origin',
                ],
            ],
            'invalid without country' => [
                'isValid' => false,
                'defaultData' => new TaxBaseExclusion(),
                'viewData' => new TaxBaseExclusion(),
                'submittedData' => [
                    'country' => null,
                    'region' => self::REGION_WITH_COUNTRY,
                    'option' => 'origin',
                ],
                'expectedData' => [
                    'country' => null,
                    'region' => $region,
                    'region_text' => null,
                    'option' => 'origin',
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
     * {@inheritDoc}
     */
    protected function getFormTypeClass(): string
    {
        return TaxBaseExclusionType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge([
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(true)
        ], parent::getExtensions());
    }
}
