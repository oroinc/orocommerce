<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsChoiceType;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomVariantFieldsChoiceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductCustomVariantFieldsChoiceType
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
            'label' => 'Size Label'
        ],
        'color' => [
            'name' => 'color',
            'type' => 'enum',
            'label' => 'Color Label'
        ],
        'boolValue' => [
            'name' => 'boolValue',
            'type' => 'boolean',
            'label' => 'Some Bool Label'
        ],
        'textValue' => [
            'name' => 'textValue',
            'type' => 'text',
            'label' => 'Some Text Label'
        ],

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

        $this->formType = new ProductCustomVariantFieldsChoiceType(
            $this->customFieldProvider,
            $this->productClass
        );
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

        $this->assertNull($form->getData());
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
                'submittedData' => [
                    $this->exampleCustomVariantFields['size']['name']
                ],
                'expectedData' => [
                    $this->exampleCustomVariantFields['size']['name']
                ]
            ],
            'size&color (enum)' => [
                'submittedData' => [
                    $this->exampleCustomVariantFields['size']['name'],
                    $this->exampleCustomVariantFields['color']['name']
                ],
                'expectedData' => [
                    $this->exampleCustomVariantFields['size']['name'],
                    $this->exampleCustomVariantFields['color']['name']
                ]
            ],
            'boolValue (bool)' => [
                'submittedData' => [
                    $this->exampleCustomVariantFields['boolValue']['name'],
                ],
                'expectedData' => [
                    $this->exampleCustomVariantFields['boolValue']['name'],
                ]
            ],
            'text value is not allowed' => [
                'submittedData' => [
                    $this->exampleCustomVariantFields['textValue']['name'],
                ],
                'expectedData' => null
            ],
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(ProductCustomVariantFieldsChoiceType::NAME, $this->formType->getName());
    }
}
