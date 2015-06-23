<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductDefaultVisibilityType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRoundingTypeType;

class ProductDefaultVisibilityTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductUnitRoundingTypeType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ProductDefaultVisibilityType();
    }

    /**
     * @dataProvider submitProvider
     *
     * @param string $expectedData
     */
    public function testSubmit($expectedData)
    {
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
            'visible' => [
                'expectedData' => Product::VISIBILITY_VISIBLE,
            ],
            'not_visible' => [
                'expectedData' => Product::VISIBILITY_NOT_VISIBLE,
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
        $this->assertEquals(ProductDefaultVisibilityType::NAME, $this->formType->getName());
    }
}
