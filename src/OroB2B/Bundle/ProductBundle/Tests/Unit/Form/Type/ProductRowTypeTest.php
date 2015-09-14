<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;

class ProductRowTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductRowType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConstraintValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new ProductRowType();

        $this->validator = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType, $this->validator);
    }

    /**
     * @dataProvider submitDataProvider
     * @param array|null $defaultData
     * @param array $submittedData
     * @param array $expectedData
     * @param array $options
     */
    public function testSubmit($defaultData, array $submittedData, array $expectedData, array $options = [])
    {
        if (count($options)) {
            $this->validator->expects($this->once())
                ->method('validate')
                ->willReturn(true);
        }

        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->getMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');
        $factory->expects($this->any())
            ->method('getInstance')
            ->willReturnCallback(
                function (Constraint $constraint) {
                    $className = $constraint->validatedBy();

                    if ($className === 'orob2b_product_product_by_sku_validator') {
                        $this->validators[$className] = $this->validator;
                    }

                    if (!isset($this->validators[$className]) ||
                        $className === 'Symfony\Component\Validator\Constraints\CollectionValidator'
                    ) {
                        $this->validators[$className] = new $className();
                    }

                    return $this->validators[$className];
                }
            );

        return $factory;
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_001',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '10'
                ],
                'expectedData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_001',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '10'
                ]
            ],
            'with default data' => [
                'defaultData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_001',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '10'
                ],
                'submittedData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_002',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '20'
                ],
                'expectedData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_002',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '20'
                ]
            ],
            'with default data and validation' => [
                'defaultData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_001',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '10'
                ],
                'submittedData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_002',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '20'
                ],
                'expectedData' => [
                    ProductRowType::PRODUCT_SKU_FIELD_NAME => 'SKU_002',
                    ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '20'
                ],
                'options' => [
                    'validation_required' => true
                ]
            ]
        ];
    }
    
    public function testGetName()
    {
        $this->assertEquals(ProductRowType::NAME, $this->formType->getName());
    }
}
