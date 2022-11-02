<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class CategoryUnitPrecisionTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision';

    /**
     * @var CategoryUnitPrecisionType
     */
    protected $formType;

    /**
     * @var CategoryDefaultProductUnitOptionsVisibilityInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $defaultProductOptionsVisibility;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->defaultProductOptionsVisibility = $this
            ->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class);

        $this->formType = new CategoryUnitPrecisionType($this->defaultProductOptionsVisibility);
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    CategoryUnitPrecisionType::class => $this->formType,
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg'),
                        ]
                    ),
                    EntityIdentifierType::class => new EntityType([
                        'kg' => (new ProductUnit())->setCode('kg'),
                    ]),
                ],
                [
                    FormType::class => [new IntegerExtension()],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @param CategoryUnitPrecision $defaultData
     * @param array $expectedOptions
     * @param array|CategoryUnitPrecision $submittedData
     * @param CategoryUnitPrecision $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        CategoryUnitPrecision $defaultData,
        array $expectedOptions,
        $submittedData,
        CategoryUnitPrecision $expectedData
    ) {
        $this->defaultProductOptionsVisibility->expects(static::any())
            ->method('isDefaultUnitPrecisionSelectionAvailable')
            ->willReturn(true);

        $form = $this->factory->create(CategoryUnitPrecisionType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertFormConfig($expectedOptions['unit'], $form->get('unit')->getConfig());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @param CategoryUnitPrecision $defaultData
     * @param array $expectedOptions
     * @param array|CategoryUnitPrecision $submittedData
     * @param CategoryUnitPrecision $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmitHidden(
        CategoryUnitPrecision $defaultData,
        array $expectedOptions,
        $submittedData,
        CategoryUnitPrecision $expectedData
    ) {
        $this->defaultProductOptionsVisibility->expects(static::any())
            ->method('isDefaultUnitPrecisionSelectionAvailable')
            ->willReturn(false);

        $form = $this->factory->create(CategoryUnitPrecisionType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertFormConfig($expectedOptions['unit'], $form->get('unit')->getConfig());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    protected function assertFormConfig(array $expectedConfig, FormConfigInterface $actualConfig)
    {
        foreach ($expectedConfig as $key => $value) {
            $this->assertTrue($actualConfig->hasOption($key));
            $this->assertEquals($value, $actualConfig->getOption($key));
        }
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'unit precision without value' => [
                'defaultData'   => new CategoryUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                    'precision' => [],
                ],
                'submittedData' => [],
                'expectedData'  => new CategoryUnitPrecision()
            ],
            'unit precision with value' => [
                'defaultData'   => new CategoryUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                    'precision' => [],
                ],
                'submittedData' => [
                    'unit' => 'kg',
                    'precision' => 5,
                ],
                'expectedData'  => (new CategoryUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(5)
            ]
        ];
    }
}
