<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\TaxBundle\Form\Type\TaxBaseExclusionType;
use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;
use OroB2B\Bundle\TaxBundle\Tests\Unit\Stub\AddressCountryAndRegionSubscriberStub;

class TaxBaseExclusionTypeTest extends FormIntegrationTestCase
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
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $country = new Country('US');
        $choices = [
            'OroAddressBundle:Country' => ['US' => $country],
            'OroAddressBundle:Region' => ['US-AL' => (new Region('US-AL'))->setCountry($country)],
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

        return [
            new PreloadedExtension(
                [
                    'oro_country' => new CountryType(),
                    'oro_region' => new RegionType(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                    'translatable_entity' => $translatableEntity,
                    CollectionType::NAME => new CollectionType(),
                ],
                [
                    'hidden' => [new RandomIdExtension()],
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            $this->getValidatorExtension(),
        ];
    }
}
