<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductVariantFieldType;
use Oro\Bundle\ProductBundle\Provider\VariantField;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductCustomVariantFieldsCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductCustomVariantFieldsCollectionType
     */
    protected $formType;

    /**
     * @var VariantFieldProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $variantFieldProvider;

    /**
     * @var string
     */
    protected $productClass = 'stdClass';

    /**
     * @var AttributeFamily
     */
    protected $attributeFamily;

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
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->variantFieldProvider = $this->getMockBuilder(VariantFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new ProductCustomVariantFieldsCollectionType(
            $this->variantFieldProvider,
            $this->productClass
        );

        $this->attributeFamily = new AttributeFamily();
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    OroCollectionType::class => new OroCollectionType(),
                    ProductVariantFieldType::class => new ProductVariantFieldType(),
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param array $variantFields
     * @param array $submittedData
     * @param string $expectedData
     */
    public function testSubmit(array $variantFields, array $submittedData, $expectedData)
    {
        $this->variantFieldProvider->expects($this->once())
            ->method('getVariantFields')
            ->with($this->attributeFamily)
            ->willReturn($variantFields);

        $form = $this->factory->create(
            ProductCustomVariantFieldsCollectionType::class,
            null,
            ['attributeFamily' => $this->attributeFamily]
        );

        $this->assertEquals($this->emptyFieldsValues, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $variantFields[] = new VariantField('size', 'Size label');
        $variantFields[] = new VariantField('color', 'Color label');
        return [
            'empty' => [
                'variantFields' => $variantFields,
                'submittedData' => [],
                'expectedData' => []
            ],
            'size (enum)' => [
                'variantFields' => $variantFields,
                'submittedData' => $this->submitCustomVariantFields['size'],
                'expectedData' => [
                    $this->exampleCustomVariantFields['size']['name']
                ]
            ],
            'size&color (enum)' => [
                'variantFields' => $variantFields,
                'submittedData' => array_merge(
                    $this->submitCustomVariantFields['size'],
                    $this->submitCustomVariantFields['color']
                ),
                'expectedData' => [
                    $this->exampleCustomVariantFields['size']['name'],
                    $this->exampleCustomVariantFields['color']['name']
                ]
            ],
            'text value is not allowed' => [
                'variantFields' => $variantFields,
                'submittedData' => $this->submitCustomVariantFields['textValue'],
                'expectedData' => []
            ],
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }
}
