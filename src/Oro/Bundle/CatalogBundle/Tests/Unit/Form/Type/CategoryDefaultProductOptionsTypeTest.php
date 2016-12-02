<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
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
     * @var SingleUnitModeService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $singleUnitModeService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->singleUnitModeService = $this->getMockBuilder(SingleUnitModeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new CategoryDefaultProductOptionsType($this->singleUnitModeService);
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $categoryUnitPrecisionType = new CategoryUnitPrecisionType();
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
     * @param boolean $isSingleUnitMode
     * @param boolean $isValid
     * @param CategoryDefaultProductOptions $defaultData
     * @param array|CategoryUnitPrecision $submittedData
     * @param CategoryDefaultProductOptions $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        $isSingleUnitMode,
        $isValid,
        CategoryDefaultProductOptions $defaultData,
        $submittedData,
        CategoryDefaultProductOptions $expectedData
    ) {
        $this->singleUnitModeService->expects(static::any())
            ->method('isSingleUnitMode')
            ->willReturn($isSingleUnitMode);

        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'UnitPrecisionWithoutValueNoSingleMode' => [
                'isSingleUnitMode' => false,
                'isValid' => true,
                'defaultData'   => new CategoryDefaultProductOptions(),
                'submittedData' => [],
                'expectedData'  => new CategoryDefaultProductOptions(),
            ],
            'UnitPrecisionWitValueNoSingleMode' => [
                'isSingleUnitMode' => false,
                'isValid' => true,
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
            'UnitPrecisionWithoutValueWithSingleMode' => [
                'isSingleUnitMode' => true,
                'isValid' => true,
                'defaultData'   => new CategoryDefaultProductOptions(),
                'submittedData' => [],
                'expectedData'  => new CategoryDefaultProductOptions(),
            ],
            'UnitPrecisionWitValueWithSingleMode' => [
                'isSingleUnitMode' => true,
                'isValid' => false,
                'defaultData'   => new CategoryDefaultProductOptions(),
                'submittedData' => [
                    'unitPrecision' => [
                        'unit' => 'kg',
                        'precision' => 5,
                    ]
                ],
                'expectedData'  => new CategoryDefaultProductOptions(),
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryDefaultProductOptionsType::NAME, $this->formType->getName());
    }
}
