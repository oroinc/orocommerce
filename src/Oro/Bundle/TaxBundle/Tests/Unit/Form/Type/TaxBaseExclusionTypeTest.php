<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;
use Oro\Bundle\TaxBundle\Form\Type\TaxBaseExclusionType;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxBaseExclusionTypeTest extends AddressFormExtensionTestCase
{
    private TaxBaseExclusionType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new TaxBaseExclusionType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(\ArrayObject::class);

        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return array_merge([
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(true)
        ], parent::getExtensions());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        self::assertArrayHasKey('data_class', $options);
        self::assertEquals(\ArrayObject::class, $options['data_class']);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        bool $isValid,
        mixed $defaultData,
        mixed $viewData,
        array $submittedData,
        array $expectedData
    ): void {
        $form = $this->factory->create(TaxBaseExclusionType::class, $defaultData);

        self::assertEquals($defaultData, $form->getData());
        self::assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        self::assertEquals($isValid, $form->isValid());

        foreach ($expectedData as $field => $data) {
            self::assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            self::assertEquals($data, $fieldForm->getData());
        }
    }

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
}
