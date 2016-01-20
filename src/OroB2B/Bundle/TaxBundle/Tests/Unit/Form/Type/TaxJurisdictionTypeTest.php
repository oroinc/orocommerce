<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;

use Doctrine\Common\Collections\ArrayCollection;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\TaxBundle\Form\Type\ZipCodeCollectionType;
use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use OroB2B\Bundle\TaxBundle\Form\Type\ZipCodeType;
use OroB2B\Bundle\TaxBundle\Form\Type\TaxJurisdictionType;
use OroB2B\Bundle\TaxBundle\Tests\Unit\Stub\AddressCountryAndRegionSubscriberStub;

class TaxJurisdictionTypeTest extends FormIntegrationTestCase
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
                    'country' => $this->country,
                    'region_text' => $this->region,
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $choices = [
            'OroAddressBundle:Country' => ['US' => $this->country],
            'OroAddressBundle:Region' => ['US-AL' => $this->region],
        ];

        $translatableEntity->expects($this->any())->method('setDefaultOptions')->will(
            $this->returnCallback(
                function (OptionsResolver $resolver) use ($choices) {
                    $choiceList = function (Options $options) use ($choices) {
                        $className = $options->offsetGet('class');
                        if (array_key_exists($className, $choices)) {
                            return new ArrayChoiceList(
                                $choices[$className],
                                function ($item) {
                                    if ($item instanceof Country) {
                                        return $item->getIso2Code();
                                    }

                                    if ($item instanceof Region) {
                                        return $item->getCombinedCode();
                                    }

                                    return $item . uniqid('form', true);
                                }
                            );
                        }

                        return new ArrayChoiceList([]);
                    };

                    $resolver->setDefault('choice_list', $choiceList);
                }
            )
        );

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

        return [
            new PreloadedExtension(
                [
                    'oro_country' => new CountryType(),
                    'oro_region' => new RegionType(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                    'translatable_entity' => $translatableEntity,
                    ZipCodeCollectionType::NAME => new ZipCodeCollectionType(),
                    ZipCodeType::NAME => $zipCodeType,
                    CollectionType::NAME => new CollectionType(),
                ],
                [
                    'hidden' => [new RandomIdExtension()],
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
        ];
    }
}
