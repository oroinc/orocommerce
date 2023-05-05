<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionType;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeType;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class TaxJurisdictionTypeTest extends AbstractAddressTestCase
{
    private TaxJurisdictionType $formType;

    protected function setUp(): void
    {
        $this->formType = new TaxJurisdictionType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(TaxJurisdiction::class);
        parent::setUp();
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(TaxJurisdictionType::class);

        $this->assertTrue($form->has('code'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('country'));
        $this->assertTrue($form->has('region'));
        $this->assertTrue($form->has('region_text'));
        $this->assertTrue($form->has('zipCodes'));
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
    ) {
        $form = $this->factory->create(TaxJurisdictionType::class, $defaultData);

        $formConfig = $form->getConfig();
        $this->assertEquals(TaxJurisdiction::class, $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());

        foreach ($expectedData as $field => $data) {
            $this->assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            $this->assertEquals($data, $fieldForm->getData());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function submitDataProvider(): array
    {
        $taxJurisdiction = new TaxJurisdiction();
        $zipCodes = [
            ZipCodeTestHelper::getRangeZipCode('12', '15')->setTaxJurisdiction($taxJurisdiction),
            ZipCodeTestHelper::getSingleValueZipCode('123')->setTaxJurisdiction($taxJurisdiction),
            ZipCodeTestHelper::getSingleValueZipCode('567')->setTaxJurisdiction($taxJurisdiction),
            ZipCodeTestHelper::getSingleValueZipCode('89')->setTaxJurisdiction($taxJurisdiction),
        ];

        [$country, $region] = $this->getValidCountryAndRegion();

        return [
            'valid tax jurisdiction' => [
                'isValid' => true,
                'defaultData' => $taxJurisdiction,
                'viewData' => $taxJurisdiction,
                'submittedData' => [
                    'code' => 'code',
                    'description' => 'description',
                    'country' => self::COUNTRY_WITH_REGION,
                    'region' => self::REGION_WITH_COUNTRY,
                    'zipCodes' => [
                        [
                            'zipRangeStart' => '12',
                            'zipRangeEnd' => '15',
                        ],
                        [
                            'zipRangeStart' => '123',
                            'zipRangeEnd' => '123',
                        ],
                        [
                            'zipRangeStart' => '567',
                            'zipRangeEnd' => null,
                        ],
                        [
                            'zipRangeStart' => null,
                            'zipRangeEnd' => '89',
                        ],
                    ],
                ],
                'expectedData' => [
                    'code' => 'code_stripped',
                    'description' => 'description',
                    'country' => $country,
                    'region' => $region,
                    'zipCodes' => new ArrayCollection($zipCodes),
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return array_merge([
            new PreloadedExtension(
                [
                    $this->formType,
                    TaxJurisdictionType::class => $this->formType,
                    new ZipCodeType()
                ],
                [
                    HiddenType::class => [new AdditionalAttrExtension()],
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            )
        ], parent::getExtensions());
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormTypeClass(): string
    {
        return TaxJurisdictionType::class;
    }
}
