<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;
use OroB2B\Bundle\AttributeBundle\AttributeType\Boolean;
use OroB2B\Bundle\AttributeBundle\AttributeType\Date;
use OroB2B\Bundle\AttributeBundle\AttributeType\DateTime;
use OroB2B\Bundle\AttributeBundle\AttributeType\Float;
use OroB2B\Bundle\AttributeBundle\AttributeType\Integer;
use OroB2B\Bundle\AttributeBundle\AttributeType\String;
use OroB2B\Bundle\AttributeBundle\AttributeType\Text;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributeTypeConstraintType;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedAttributePropertyType;
use OroB2B\Bundle\AttributeBundle\Form\Type\SharingTypeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\UpdateAttributeType;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer as IntegerConstraint;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal as DecimalConstraint;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\AttributeDisabledFieldsTransformer;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\AttributeTransformer;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributeTypeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\WebsiteAttributePropertyType;
use OroB2B\Bundle\AttributeBundle\AttributeType\MultiSelect;
use OroB2B\Bundle\AttributeBundle\AttributeType\Select;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class UpdateAttributeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttributeTypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var UpdateAttributeType
     */
    protected $formType;

    protected function setUp()
    {
        $this->initializeFormType();
    }

    protected function initializeFormType()
    {
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->typeRegistry = new AttributeTypeRegistry();
        $this->typeRegistry->addType(new String());
        $this->typeRegistry->addType(new Text());
        $this->typeRegistry->addType(new Date());
        $this->typeRegistry->addType(new DateTime());
        $this->typeRegistry->addType(new Integer());
        $this->typeRegistry->addType(new Float());
        $this->typeRegistry->addType(new Boolean());
        $this->typeRegistry->addType(new Select());
        $this->typeRegistry->addType(new MultiSelect());

        $this->formType = new UpdateAttributeType($this->managerRegistry, $this->typeRegistry);
    }

    /**
     * @param Attribute $attribute
     * @param array $expectedCalls
     * @dataProvider buildFormDataTransformer
     */
    public function testBuildForm(Attribute $attribute, array $expectedCalls)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        foreach ($expectedCalls as $index => $data) {
            if (isset($data['method'])) {
                $method = $data['method'];
                $arguments = $data['arguments'];
            } else {
                $method = 'add';
                $arguments = $data;
            }

            $mocker = $builder->expects($this->at($index))
                ->method($method);
            call_user_func_array([$mocker, 'with'], $arguments);
            $mocker->willReturnSelf();
        }

        $this->formType->buildForm($builder, ['data' => $attribute]);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildFormDataTransformer()
    {
        $this->initializeFormType();

        // possible form calls
        $addCode = ['code', 'hidden'];
        $addType = ['type', 'hidden'];
        $addLocalized = ['localized', 'checkbox', ['label' => 'orob2b.attribute.localized.label', 'required' => false]];
        $addSharingType = [
            'sharingType',
            SharingTypeType::NAME,
            [
                'label' => 'orob2b.attribute.sharing_type.label',
                'required' => false,
                'constraints' => [new NotBlank()],
                'validation_groups' => ['Default']
            ],
        ];
        $addLabel = [
            'label',
            LocalizedAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.labels.label',
                'type' => 'text',
                'options' => ['constraints' => [new NotBlank()], 'validation_groups' => ['Default']]
            ]
        ];
        $addRequired = ['required', 'checkbox', ['label' => 'orob2b.attribute.required.label', 'required' => false]];
        $addUnique = ['unique', 'checkbox', ['label' => 'orob2b.attribute.unique.label', 'required' => false]];
        $addPreSubmitListener = [
            'method' => 'addEventListener',
            'arguments' => [FormEvents::PRE_SUBMIT, [$this->formType, 'onPreSubmit']],
        ];
        $addOnProductView = [
            'onProductView',
            WebsiteAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.on_product_view',
                'required' => false,
                'type' => 'checkbox',
            ]
        ];
        $addInProductListing = [
            'inProductListing',
            WebsiteAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.in_product_listing',
                'required' => false,
                'type' => 'checkbox',
            ]
        ];
        $addUseInSorting = [
            'useInSorting',
            WebsiteAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.use_in_sorting',
                'required' => false,
                'type' => 'checkbox',
            ]
        ];
        $addOnAdvancedSearch = [
            'onAdvancedSearch',
            WebsiteAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.on_advanced_search',
                'required' => false,
                'type' => 'checkbox',
            ]
        ];
        $addOnProductComparison = [
            'onProductComparison',
            WebsiteAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.on_product_comparison',
                'required' => false,
                'type' => 'checkbox',
            ]
        ];
        $addContainHtml = [
            'containHtml',
            'checkbox',
            ['label' => 'orob2b.attribute.contain_html.label', 'required' => false]
        ];
        $addUseForSearch = [
            'useForSearch',
            WebsiteAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.use_for_search',
                'required' => false,
                'type' => 'checkbox',
            ]
        ];
        $addUseInFilters = [
            'useInFilters',
            WebsiteAttributePropertyType::NAME,
            [
                'label' => 'orob2b.attribute.attributeproperty.fields.use_in_filters',
                'required' => false,
                'type' => 'checkbox',
            ]
        ];
        $addCodeDisabled = [
            'codeDisabled',
            'text',
            ['label' => 'orob2b.attribute.code.label', 'disabled' => true]
        ];
        $addTypeDisabled = [
            'typeDisabled',
            AttributeTypeType::NAME,
            ['label' => 'orob2b.attribute.type.label', 'disabled' => true]
        ];
        $addDisabledFieldsTransformer = [
            'method' => 'addViewTransformer',
            'arguments' => [new AttributeDisabledFieldsTransformer()]
        ];

        // possible attributes
        $stringLocalizedAttribute = new Attribute();
        $stringLocalizedAttribute->setType(String::NAME)
            ->setLocalized(true);

        $textNotLocalizedAttribute = new Attribute();
        $textNotLocalizedAttribute->setType(Text::NAME)
            ->setLocalized(false);

        $integerLocalizedAttribute = new Attribute();
        $integerLocalizedAttribute->setType(Integer::NAME)
            ->setLocalized(true);

        $floatNotLocalizedAttribute = new Attribute();
        $floatNotLocalizedAttribute->setType(Float::NAME)
            ->setLocalized(false);

        $dateLocalizedAttribute = new Attribute();
        $dateLocalizedAttribute->setType(Date::NAME)
            ->setLocalized(true);

        $datetimeNotLocalizedAttribute = new Attribute();
        $datetimeNotLocalizedAttribute->setType(DateTime::NAME)
            ->setLocalized(false);

        $booleanLocalizedAttribute = new Attribute();
        $booleanLocalizedAttribute->setType(Boolean::NAME)
            ->setLocalized(true);

        $selectNotLocalizedAttribute = new Attribute();
        $selectNotLocalizedAttribute->setType(Select::NAME)
            ->setLocalized(false);

        $selectLocalizedAttribute = new Attribute();
        $selectLocalizedAttribute->setType(Select::NAME)
            ->setLocalized(true);

        $multiSelectNotLocalizedAttribute = new Attribute();
        $multiSelectNotLocalizedAttribute->setType(MultiSelect::NAME)
            ->setLocalized(false);

        $multiSelectLocalizedAttribute = new Attribute();
        $multiSelectLocalizedAttribute->setType(MultiSelect::NAME)
            ->setLocalized(true);

        return [
            'string' => [
                'attribute' => $stringLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    $addUnique,
                    $this->addValidation(new String()),
                    [
                        'defaultValue',
                        LocalizedAttributePropertyType::NAME,
                        [
                            'label' => 'orob2b.attribute.default_values.label',
                            'required' => false,
                            'type' => 'text',
                            'options' => ['required' => false],
                        ]
                    ],
                    $addPreSubmitListener,
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addContainHtml,
                    $addUseForSearch,
                    $this->addAttributeTransformer($stringLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'text' => [
                'attribute' => $textNotLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    $addUnique,
                    $this->addValidation(new Text()),
                    [
                        'defaultValue',
                        'textarea',
                        ['label' => 'orob2b.attribute.default_values.label', 'required' => false]
                    ],
                    $addPreSubmitListener,
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addContainHtml,
                    $addUseForSearch,
                    $this->addAttributeTransformer($textNotLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'integer' => [
                'attribute' => $integerLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    $addUnique,
                    $this->addValidation(new Integer()),
                    [
                        'defaultValue',
                        LocalizedAttributePropertyType::NAME,
                        [
                            'label' => 'orob2b.attribute.default_values.label',
                            'required' => false,
                            'type' => 'integer',
                            'options' => [
                                'required' => false,
                                'type' => 'text',
                                'constraints' => [new IntegerConstraint()],
                                'validation_groups' => ['Default'],
                            ],
                        ]
                    ],
                    $addPreSubmitListener,
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addUseInFilters,
                    $this->addAttributeTransformer($integerLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'float' => [
                'attribute' => $floatNotLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    $addUnique,
                    $this->addValidation(new Float()),
                    [
                        'defaultValue',
                        'number',
                        [
                            'label' => 'orob2b.attribute.default_values.label',
                            'required' => false,
                            'constraints' => [new DecimalConstraint()],
                            'validation_groups' => ['Default'],
                        ]
                    ],
                    $addPreSubmitListener,
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addUseInFilters,
                    $this->addAttributeTransformer($floatNotLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'date' => [
                'attribute' => $dateLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    $addUnique,
                    [
                        'defaultValue',
                        LocalizedAttributePropertyType::NAME,
                        [
                            'label' => 'orob2b.attribute.default_values.label',
                            'required' => false,
                            'type' => 'oro_date',
                            'options' => ['required' => false],
                        ]
                    ],
                    $addPreSubmitListener,
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $this->addAttributeTransformer($dateLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'datetime' => [
                'attribute' => $datetimeNotLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    $addUnique,
                    [
                        'defaultValue',
                        'oro_datetime',
                        ['label' => 'orob2b.attribute.default_values.label', 'required' => false]
                    ],
                    $addPreSubmitListener,
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $this->addAttributeTransformer($datetimeNotLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'boolean' => [
                'attribute' => $booleanLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    [
                        'defaultValue',
                        LocalizedAttributePropertyType::NAME,
                        [
                            'label' => 'orob2b.attribute.default_values.label',
                            'required' => false,
                            'type' => 'checkbox',
                            'options' => ['required' => false],
                        ]
                    ],
                    $addPreSubmitListener,
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addUseInFilters,
                    $this->addAttributeTransformer($booleanLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'select not localized' => [
                'attribute' => $selectNotLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    [
                        'defaultOptions',
                        'options_not_localized',
                        [
                            'label' => 'orob2b.attribute.options.label',
                            'required' => false,
                        ]
                    ],
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addUseForSearch,
                    $addUseInFilters,
                    $this->addAttributeTransformer($selectNotLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'select localized' => [
                'attribute' => $selectLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    [
                        'defaultOptions',
                        'options_localized',
                        [
                            'label' => 'orob2b.attribute.options.label',
                            'required' => false,
                        ]
                    ],
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addUseForSearch,
                    $addUseInFilters,
                    $this->addAttributeTransformer($selectLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'multiselect not localized' => [
                'attribute' => $multiSelectNotLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    [
                        'defaultOptions',
                        'options_multiple_not_localized',
                        [
                            'label' => 'orob2b.attribute.options.label',
                            'required' => false,
                        ]
                    ],
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addUseForSearch,
                    $addUseInFilters,
                    $this->addAttributeTransformer($multiSelectNotLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
            'multiselect localized' => [
                'attribute' => $multiSelectLocalizedAttribute,
                'expectedCalls' => [
                    $addCode,
                    $addType,
                    $addLocalized,
                    $addSharingType,
                    $addLabel,
                    $addRequired,
                    [
                        'defaultOptions',
                        'options_multiple_localized',
                        [
                            'label' => 'orob2b.attribute.options.label',
                            'required' => false,
                        ]
                    ],
                    $addOnProductView,
                    $addInProductListing,
                    $addUseInSorting,
                    $addOnAdvancedSearch,
                    $addOnProductComparison,
                    $addUseForSearch,
                    $addUseInFilters,
                    $this->addAttributeTransformer($multiSelectLocalizedAttribute),
                    $addCodeDisabled,
                    $addTypeDisabled,
                    $addDisabledFieldsTransformer
                ]
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Attribute", "DateTime" given
     */
    public function testBuildFormNotAttributeException()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->formType->buildForm($builder, ['data' => new \DateTime()]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage Attribute type "unknown" not found
     */
    public function testBuildFormUnknownAttributeTypeException()
    {
        $attribute = new Attribute();
        $attribute->setType('unknown');

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->formType->buildForm($builder, ['data' => $attribute]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage Form type is required for attribute type "invalid"
     */
    public function testBuildFormNoFormTypeScalarException()
    {
        $type = 'invalid';

        /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeTypeInterface $invalidAttributeType */
        $invalidAttributeType = $this->getMock('OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface');
        $invalidAttributeType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($type));
        $invalidAttributeType->expects($this->any())
            ->method('getFormParameters')
            ->will($this->returnValue([]));

        $this->typeRegistry->addType($invalidAttributeType);

        $attribute = new Attribute();
        $attribute->setType($type);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->any())
            ->method('add')
            ->willReturnSelf();
        $this->formType->buildForm($builder, ['data' => $attribute]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage Form type is required for attribute type "invalid"
     */
    public function testBuildFormNoFormTypeOptionsException()
    {
        $type = 'invalid';

        /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeTypeInterface $invalidAttributeType */
        $invalidAttributeType
            = $this->getMock('OroB2B\Bundle\AttributeBundle\AttributeType\OptionAttributeTypeInterface');
        $invalidAttributeType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($type));
        $invalidAttributeType->expects($this->any())
            ->method('getDefaultValueFormParameters')
            ->will($this->returnValue([]));

        $this->typeRegistry->addType($invalidAttributeType);

        $attribute = new Attribute();
        $attribute->setType($type);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->any())
            ->method('add')
            ->willReturnSelf();
        $this->formType->buildForm($builder, ['data' => $attribute]);
    }

    /**
     * @param string $formType
     * @param $inputData
     * @param $expectedData
     * @dataProvider onPreSubmitDataProvider
     */
    public function testOnPreSubmit($formType, array $inputData, array $expectedData)
    {
        $defaultFormType = $this->getMock('Symfony\Component\Form\ResolvedFormTypeInterface');
        $defaultFormType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($formType));

        $defaultValueFormConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $defaultValueFormConfig->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($defaultFormType));

        $defaultValueForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $defaultValueForm->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($defaultValueFormConfig));

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('get')
            ->with('defaultValue')
            ->will($this->returnValue($defaultValueForm));

        $event = new FormEvent($form, $inputData);
        $this->formType->onPreSubmit($event);
        $this->assertEquals($expectedData, $event->getData());
    }

    public function onPreSubmitDataProvider()
    {
        return [
            'localized form and localized value' => [
                'formType' => LocalizedAttributePropertyType::NAME,
                'inputData' => [
                    'defaultValue' => [LocalizedAttributePropertyType::FIELD_DEFAULT => 'test']
                ],
                'expectedData' => [
                    'defaultValue' => [LocalizedAttributePropertyType::FIELD_DEFAULT => 'test']
                ],
            ],
            'not localized form and not localized value' => [
                'formType' => 'text',
                'inputData' => ['defaultValue' => 'test'],
                'expectedData' => ['defaultValue' => 'test'],
            ],
            'localized form and not localized value' => [
                'formType' => LocalizedAttributePropertyType::NAME,
                'inputData' => ['defaultValue' => 'test'],
                'expectedData' => [
                    'defaultValue' => [LocalizedAttributePropertyType::FIELD_DEFAULT => 'test']
                ],
            ],
            'localized form and not localized value for boolean attribute' => [
                'formType' => LocalizedAttributePropertyType::NAME,
                'inputData' => [],
                'expectedData' => [
                    'defaultValue' => [LocalizedAttributePropertyType::FIELD_DEFAULT => null]
                ],
            ],
            'not localized form and localized value' => [
                'formType' => 'text',
                'inputData' => [
                    'defaultValue' => [LocalizedAttributePropertyType::FIELD_DEFAULT => 'test']
                ],
                'expectedData' => ['defaultValue' => 'test'],
            ],
            'not localized form and localized value for boolean attribute' => [
                'formType' => 'text',
                'inputData' => [
                    'defaultValue' => [LocalizedAttributePropertyType::FIELD_LOCALES => []]
                ],
                'expectedData' => ['defaultValue' => null],
            ],
        ];
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['data']);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => null, 'validation_groups' => ['Update']]);

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(UpdateAttributeType::NAME, $this->formType->getName());
    }

    /**
     * @param AttributeTypeInterface $attributeType
     * @return array
     */
    protected function addValidation($attributeType)
    {
        return [
            'validation',
            AttributeTypeConstraintType::NAME,
            [
                'label' => 'orob2b.attribute.validation.label',
                'required' => false,
                'attribute_type' => $attributeType
            ]
        ];
    }

    /**
     * @param Attribute $attribute
     * @return array
     */
    protected function addAttributeTransformer(Attribute $attribute)
    {
        return [
            'method' => 'addViewTransformer',
            'arguments' => [new AttributeTransformer($this->managerRegistry, $this->typeRegistry, $attribute)]
        ];
    }
}
