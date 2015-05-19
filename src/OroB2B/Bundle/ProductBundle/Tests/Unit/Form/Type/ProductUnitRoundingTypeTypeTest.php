<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRoundingTypeType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

class ProductUnitRoundingTypeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductUnitRoundingTypeType
     */
    protected $formType;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->formType = new ProductUnitRoundingTypeType($this->translator);
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
            'half_up rounding' => [
                'expectedData' => RoundingService::HALF_UP,
            ],
            'half_down rounding' => [
                'expectedData' => RoundingService::HALF_DOWN,
            ],
            'ceil rounding' => [
                'expectedData' => RoundingService::CEIL,
            ],
            'floor rounding' => [
                'expectedData' => RoundingService::FLOOR,
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
        $this->assertEquals(ProductUnitRoundingTypeType::NAME, $this->formType->getName());
    }
}
