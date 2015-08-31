<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductCustomFieldsChoiceType;
use OroB2B\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomFieldsChoiceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductCustomFieldsChoiceType
     */
    protected $formType;

    /**
     * @var CustomFieldProvider
     */
    protected $customFieldProvider;

    /**
     * @var string
     */
    protected $productClass = 'stdClass';

    /**
     * @var array
     */
    protected $exampleCustomFields = [
        'size' => [
            'name' => 'size',
            'label' => 'Size Label'
        ],
        'color' => [
            'name' => 'color',
            'label' => 'Color Label'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->customFieldProvider = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\CustomFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new ProductCustomFieldsChoiceType($this->customFieldProvider, $this->productClass);
    }

    /**
     * @dataProvider submitProvider
     *
     * @param string $expectedData
     */
    public function testSubmit($expectedData)
    {
        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->willReturn($this->exampleCustomFields);

        $form = $this->factory->create($this->formType);

        $this->assertNull($form->getData());
        $form->submit($expectedData);
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
                'expectedData' => []
            ],
            'size' => [
                'expectedData' => [
                    $this->exampleCustomFields['size']['name']
                ]
            ],
            'size&color' => [
                'expectedData' => [
                    $this->exampleCustomFields['size']['name'],
                    $this->exampleCustomFields['color']['name']
                ]
            ]
        ];
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(ProductCustomFieldsChoiceType::NAME, $this->formType->getName());
    }
}
