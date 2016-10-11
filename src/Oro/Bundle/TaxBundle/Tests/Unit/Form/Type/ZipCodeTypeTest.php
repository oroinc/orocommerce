<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\ExecutionContext;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeType;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFields;

class ZipCodeTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\TaxBundle\Entity\ZipCode';

    /**
     * @var ZipCodeType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ZipCodeType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('oro_tax_zip_code_type', $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     * @param string $submittedData
     * @param string $expectedData
     * @param bool $valid
     */
    public function testSubmit(
        $submittedData,
        $expectedData,
        $valid
    ) {
        $form = $this->factory->create($this->formType);

        $transformers = $form->getConfig()->getModelTransformers();
        $this->assertCount(1, $transformers);
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer', $transformers[0]);


        $form->submit($submittedData);
        $this->assertEquals($valid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'different range' => [
                'submittedData' => [
                    'zipRangeStart' => '00123',
                    'zipRangeEnd' => '00234',
                ],
                'expectedData' => ZipCodeTestHelper::getRangeZipCode('00123', '00234'),
                'valid' => true,
            ],
            'same range' => [
                'submittedData' => [
                    'zipRangeStart' => '00123',
                    'zipRangeEnd' => '00123',
                ],
                'expectedData' => ZipCodeTestHelper::getSingleValueZipCode('00123'),
                'valid' => true,
            ],
            'start range only' => [
                'submittedData' => [
                    'zipRangeStart' => '00123',
                    'zipRangeEnd' => null,
                ],
                'expectedData' => ZipCodeTestHelper::getSingleValueZipCode('00123'),
                'valid' => true,
            ],
            'end range only' => [
                'submittedData' => [
                    'zipRangeStart' => null,
                    'zipRangeEnd' => '00123',
                ],
                'expectedData' => ZipCodeTestHelper::getSingleValueZipCode('00123'),
                'valid' => true,
            ],
            'alphanumeric zip code' => [
                'submittedData' => [
                    'zipRangeStart' => '1A30D',
                    'zipRangeEnd' => '1A32B',
                ],
                'expectedData' => ZipCodeTestHelper::getRangeZipCode('1A30D', '1A32B'),
                'valid' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getValidators()
    {
        $zipCodeFieldsConstraint = new ZipCodeFields();
        $zipCodeFieldsValidator = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFieldsValidator')
            ->setMethods(['initialize', 'isInteger'])
            ->getMock();

        $zipCodeFieldsValidator->expects($this->any())->method('isInteger')->willReturnCallback(
            function ($value) {
                $value = str_replace('0', '', $value);

                return filter_var($value, FILTER_VALIDATE_INT);
            }
        );
        $zipCodeFieldsValidator->expects($this->any())->method('initialize')->willReturnCallback(
            function (ExecutionContext $legacyContext) use ($zipCodeFieldsValidator) {
                $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
                $builder = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');

                $context->expects($this->any())->method('buildViolation')->with($this->isType('string'))
                    ->willReturnCallback(
                        function ($message) use ($builder, $legacyContext) {
                            $constraint = new ZipCodeFields();
                            if ($message === $constraint->onlyNumericRangesSupported) {
                                $legacyContext->addViolation($constraint->onlyNumericRangesSupported);
                            }

                            return $builder;
                        }
                    );

                $builder->expects($this->any())->method('atPath')->with($this->isType('string'))->willReturn($builder);
                $builder->expects($this->any())->method('addViolation');

                $prop = new \ReflectionProperty(get_class($zipCodeFieldsValidator), 'context');
                $prop->setAccessible(true);
                $prop->setValue($zipCodeFieldsValidator, $context);

                return true;
            }
        );

        return [
            $zipCodeFieldsConstraint->validatedBy() => $zipCodeFieldsValidator,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            $this->getValidatorExtension(true),
        ];
    }
}
