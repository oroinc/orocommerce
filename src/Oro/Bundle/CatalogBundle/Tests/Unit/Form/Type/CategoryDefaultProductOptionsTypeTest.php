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
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class CategoryDefaultProductOptionsTypeTest extends FormIntegrationTestCase
{
    private const DATA_CLASS = CategoryDefaultProductOptions::class;

    /** @var CategoryUnitPrecisionType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new CategoryDefaultProductOptionsType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $visibility = $this->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class);
        $visibility->expects(self::any())
            ->method('isDefaultUnitPrecisionSelectionAvailable')
            ->willReturn(true);

        $categoryUnitPrecisionType = new CategoryUnitPrecisionType($visibility);
        $categoryUnitPrecisionType->setDataClass(CategoryUnitPrecision::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    CategoryUnitPrecisionType::class => $categoryUnitPrecisionType,
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub([
                        'item' => (new ProductUnit())->setCode('item'),
                        'kg' => (new ProductUnit())->setCode('kg')
                    ])
                ],
                [
                    FormType::class => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        CategoryDefaultProductOptions $defaultData,
        array|CategoryUnitPrecision $submittedData,
        CategoryDefaultProductOptions $expectedData
    ) {
        $form = $this->factory->create(CategoryDefaultProductOptionsType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
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
}
