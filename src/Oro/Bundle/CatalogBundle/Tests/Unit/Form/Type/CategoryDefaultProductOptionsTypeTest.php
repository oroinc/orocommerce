<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class CategoryDefaultProductOptionsTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions';

    /**
     * @var CategoryUnitPrecisionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new CategoryDefaultProductOptionsType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $categoryUnitPrecisionType = new CategoryUnitPrecisionType($this->getVisibilityMock());
        $categoryUnitPrecisionType->setDataClass('Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision');
        return [
            new PreloadedExtension(
                [
                    CategoryUnitPrecisionType::NAME => $categoryUnitPrecisionType,
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionTypeStub(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg')
                        ]
                    )
                ],
                [
                    'form' => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param CategoryDefaultProductOptions $defaultData
     * @param array|CategoryUnitPrecision $submittedData
     * @param CategoryDefaultProductOptions $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        CategoryDefaultProductOptions $defaultData,
        $submittedData,
        CategoryDefaultProductOptions $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

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
            'UnitPrecisionWithoutValue' => [
                'defaultData'   => new CategoryDefaultProductOptions(),
                'submittedData' => [],
                'expectedData'  => new CategoryDefaultProductOptions(),
            ],
            'UnitPrecisionWitValue' => [
                'defaultData'   => new CategoryDefaultProductOptions(),
                'submittedData' => [
                    'unitPrecision' => [
                        'unit' => 'kg',
                        'precision' => 5,
                    ]
                ],
                'expectedData'  => (new CategoryDefaultProductOptions())
                    ->setUnitPrecision((new CategoryUnitPrecision())
                        ->setUnit((new ProductUnit())->setCode('kg'))
                        ->setPrecision(5))
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryDefaultProductOptionsType::NAME, $this->formType->getName());
    }

    /**
     * @return CategoryDefaultProductUnitOptionsVisibilityInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getVisibilityMock()
    {
        $defaultProductOptionsVisibility = $this
            ->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class);
        $defaultProductOptionsVisibility->expects(static::any())
            ->method('isDefaultUnitPrecisionSelectionAvailable')
            ->willReturn(true);

        return $defaultProductOptionsVisibility;
    }
}
