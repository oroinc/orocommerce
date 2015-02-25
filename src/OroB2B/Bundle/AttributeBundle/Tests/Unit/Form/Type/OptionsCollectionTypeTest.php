<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionRowType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionsCollectionType;
use OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub\IntegerType;
use OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub\TextType;
use OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\AbstractLocalizedType;

use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\PreloadedExtension;

class OptionsCollectionTypeTest extends AbstractLocalizedType
{
    /**
     * @var OptionsCollectionType
     */
    protected $formType;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $builder = new FormFactoryBuilder();
        $builder->addExtensions($this->getExtensions())
            ->addExtension(new CoreExtension());

        $this->factory = $builder->getFormFactory();

        $this->formType = new OptionsCollectionType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'text' => new TextType(),
                    'integer' => new IntegerType(),
                    'oro_collection' => new CollectionType(),
                    OptionRowType::NAME => new OptionRowType(),
                    LocaleCollectionType::NAME => new LocaleCollectionType($this->registry),
                    FallbackValueType::NAME => new FallbackValueType(),
                    FallbackPropertyType::NAME => new FallbackPropertyType(),
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $defaultData, $submittedData, $expectedData)
    {
        $this->setRegistryExpectations();

        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'option without submitted data' => [
                'options' => [],
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => [],
            ],
            'option without data' => [
                'options' => [],
                'defaultData' => null,
                'submittedData' => [
                    [
                        OptionRowType::DEFAULT_VALUE => 'first option default value',
                        OptionRowType::ORDER => '10',
                        OptionRowType::MASTER_OPTION_ID => null
                    ],
                    [
                        OptionRowType::DEFAULT_VALUE => 'second option default value',
                        OptionRowType::ORDER => '20',
                        OptionRowType::MASTER_OPTION_ID => null
                    ]
                ],
                'expectedData' => [
                    [
                        OptionRowType::MASTER_OPTION_ID => null,
                        'order' => '10',
                        'data' => [
                            null => [
                                'value' => 'first option default value',
                                'is_default' => false,
                            ],
                            1 => [
                                'value' => null,
                                'is_default' => false,
                            ],
                            2 => [
                                'value' => null,
                                'is_default' => false,
                            ],
                            3 => [
                                'value' => null,
                                'is_default' => false,
                            ]
                        ]
                    ],
                    [
                        OptionRowType::MASTER_OPTION_ID => null,
                        'order' => '20',
                        'data' => [
                            null => [
                                'value' => 'second option default value',
                                'is_default' => false,
                            ],
                            1 => [
                                'value' => null,
                                'is_default' => false,
                            ],
                            2 => [
                                'value' => null,
                                'is_default' => false,
                            ],
                            3 => [
                                'value' => null,
                                'is_default' => false,
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(OptionsCollectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_collection', $this->formType->getParent());
    }
}
