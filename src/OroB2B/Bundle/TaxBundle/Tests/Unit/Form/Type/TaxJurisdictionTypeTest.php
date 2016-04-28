<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;

use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Form\Type\ZipCodeCollectionType;
use OroB2B\Bundle\TaxBundle\Form\Type\ZipCodeType;
use OroB2B\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use OroB2B\Bundle\TaxBundle\Form\Type\TaxJurisdictionType;

class TaxJurisdictionTypeTest extends AbstractAddressTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction';
    const ZIP_CODE_DATA_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\ZipCode';

    /**
     * @var TaxJurisdictionType
     */
    protected $formType;

    /**
     * @var Country
     */
    protected $country;

    /**
     * @var Region
     */
    protected $region;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->country = new Country('US');
        $this->region = (new Region('US-AL'))->setCountry($this->country);

        $this->formType = new TaxJurisdictionType(new AddressCountryAndRegionSubscriberStub());
        $this->formType->setDataClass(static::DATA_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType, $this->country, $this->region);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_tax_jurisdiction_type', $this->formType->getName());
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType);

        $this->assertTrue($form->has('code'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('country'));
        $this->assertTrue($form->has('region'));
        $this->assertTrue($form->has('region_text'));
        $this->assertTrue($form->has('zipCodes'));
    }

    /**
     * @dataProvider submitDataProvider
     * @param bool $isValid
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(
        $isValid,
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData);

        $formConfig = $form->getConfig();
        $this->assertEquals(static::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());

        foreach ($expectedData as $field => $data) {
            $this->assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            $this->assertEquals($data, $fieldForm->getData());
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $taxJurisdiction = new TaxJurisdiction();
        $zipCodes = [
            ZipCodeTestHelper::getRangeZipCode('12', '15')->setTaxJurisdiction($taxJurisdiction),
            ZipCodeTestHelper::getSingleValueZipCode('123')->setTaxJurisdiction($taxJurisdiction),
            ZipCodeTestHelper::getSingleValueZipCode('567')->setTaxJurisdiction($taxJurisdiction),
            ZipCodeTestHelper::getSingleValueZipCode('89')->setTaxJurisdiction($taxJurisdiction),
        ];

        $countryUS = new Country('US');
        $regionUSAL = new Region('US-AL');
        $regionUSAL->setCountry($countryUS);

        return [
            'valid tax jurisdiction' => [
                'isValid' => true,
                'defaultData' => $taxJurisdiction,
                'viewData' => $taxJurisdiction,
                'submittedData' => [
                    'code' => 'code',
                    'description' => 'description',
                    'country' => 'US',
                    'region' => 'US-AL',
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
                    'code' => 'code',
                    'description' => 'description',
                    'country' => $countryUS,
                    'region' => $regionUSAL,
                    'zipCodes' => new ArrayCollection($zipCodes),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider $configProvider */
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|Translator $translator */
        $translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $zipCodeType = new ZipCodeType();
        $zipCodeType->setDataClass(self::ZIP_CODE_DATA_CLASS);

        return array_merge([
            new PreloadedExtension(
                [
                    ZipCodeCollectionType::NAME => new ZipCodeCollectionType(),
                    ZipCodeType::NAME => $zipCodeType,
                    CollectionType::NAME => new CollectionType(),
                ],
                [
                    'hidden' => [new AdditionalAttrExtension()],
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            )
        ], parent::getExtensions());
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return $this->formType;
    }
}
