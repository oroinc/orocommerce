<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use OroB2B\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;

class CategoryDefaultProductOptionsTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions';

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
        $categoryUnitPrecisionType = new CategoryUnitPrecisionType();
        $categoryUnitPrecisionType->setDataClass('OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision');
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
     * @param array $expectedOptions
     * @param array|CategoryUnitPrecision $submittedData
     * @param CategoryDefaultProductOptions $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        CategoryDefaultProductOptions $defaultData,
        array $expectedOptions,
        $submittedData,
        CategoryDefaultProductOptions $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertFormConfig($expectedOptions['unitPrecision'], $form->get('unitPrecision')->getConfig());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @param array $expectedConfig
     * @param FormConfigInterface $actualConfig
     */
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
                'defaultData'   => new CategoryDefaultProductOptions(),
                'expectedOptions' => [
                    'unitPrecision' => [],
                ],
                'submittedData' => [],
                'expectedData'  => new CategoryDefaultProductOptions(),
            ],
            'unit precision with value' => [
                'defaultData'   => new CategoryDefaultProductOptions(),
                'expectedOptions' => [
                    'unitPrecision' => [],
                ],
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
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryDefaultProductOptionsType::NAME, $this->formType->getName());
    }
}
