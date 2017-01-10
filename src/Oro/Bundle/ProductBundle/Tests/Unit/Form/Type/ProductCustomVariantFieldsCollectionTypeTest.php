<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductVariantFieldType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsCollectionType;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductCustomVariantFieldsCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductCustomVariantFieldsCollectionType
     */
    protected $formType;

    /**
     * @var CustomFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customFieldProvider;

    /**
     * @var string
     */
    protected $productClass = 'stdClass';

    /**
     * @var array
     */
    protected $exampleCustomVariantFields = [
        'size' => [
            'name' => 'size',
            'type' => 'enum',
            'label' => 'Size Label',
            'is_serialized' => false,
        ],
        'color' => [
            'name' => 'color',
            'type' => 'enum',
            'label' => 'Color Label',
            'is_serialized' => false,
        ],
        'boolValue' => [
            'name' => 'boolValue',
            'type' => 'boolean',
            'label' => 'Some Bool Label',
            'is_serialized' => false,
        ],
        'boolSerializedValue' => [
            'name' => 'boolSerializedValue',
            'type' => 'boolean',
            'label' => 'Some Serialized Bool Label',
            'is_serialized' => true,
        ],
        'textValue' => [
            'name' => 'textValue',
            'type' => 'text',
            'label' => 'Some Text Label',
            'is_serialized' => false,
        ],
    ];

    /**
     * @var array
     */
    protected $submitCustomVariantFields = [
        'size' => [
            'size' => [
                'priority' => 0,
                'is_selected' => true,
            ],
        ],
        'color' => [
            'color' => [
                'priority' => 1,
                'is_selected' => true,
            ],
        ],
        'boolValue' => [
            'boolValue' => [
                'priority' => 2,
                'is_selected' => true,
            ],
        ],
        'boolSerializedValue' => [
            'boolSerializedValue' => [
                'priority' => 3,
                'is_selected' => true,
            ],
        ],
        'textValue' => [
            'textValue' => [
                'priority' => 4,
                'is_selected' => true,
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $emptyFieldsValues = [
        'size' => [
            'priority' => 9999,
            'is_selected' => false,
        ],
        'color' => [
            'priority' => 9999,
            'is_selected' => false,
        ],
        'boolValue' => [
            'priority' => 9999,
            'is_selected' => false,
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new ProductCustomVariantFieldsCollectionType(
            $this->customFieldProvider,
            $this->productClass
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroCollectionType::NAME => new OroCollectionType(),
                    ProductVariantFieldType::NAME => new ProductVariantFieldType(),
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param array $submittedData
     * @param string $expectedData
     */
    public function testSubmit(array $submittedData, $expectedData)
    {
        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->willReturn($this->exampleCustomVariantFields);

        $form = $this->factory->create($this->formType);

        $this->assertEquals($this->emptyFieldsValues, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty' => [
                'submittedData' => [],
                'expectedData' => []
            ],
            'size (enum)' => [
                'submittedData' => $this->submitCustomVariantFields['size'],
                'expectedData' => [
                    $this->exampleCustomVariantFields['size']['name']
                ]
            ],
            'size&color (enum)' => [
                'submittedData' => array_merge(
                    $this->submitCustomVariantFields['size'],
                    $this->submitCustomVariantFields['color']
                ),
                'expectedData' => [
                    $this->exampleCustomVariantFields['size']['name'],
                    $this->exampleCustomVariantFields['color']['name']
                ]
            ],
            'boolValue (bool)' => [
                'submittedData' => $this->submitCustomVariantFields['boolValue'],
                'expectedData' => [
                    $this->exampleCustomVariantFields['boolValue']['name'],
                ]
            ],
            'text value is not allowed' => [
                'submittedData' => $this->submitCustomVariantFields['textValue'],
                'expectedData' => []
            ],
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_collection', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(ProductCustomVariantFieldsCollectionType::NAME, $this->formType->getName());
    }
}
