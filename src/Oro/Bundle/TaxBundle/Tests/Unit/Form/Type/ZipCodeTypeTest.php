<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;
use Oro\Bundle\TaxBundle\Form\Type\ZipCodeType;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFields;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFieldsValidator;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ZipCodeTypeTest extends FormIntegrationTestCase
{
    /** @var ZipCodeType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new ZipCodeType();
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $submittedData,
        ZipCode $expectedData,
        bool $valid
    ) {
        $form = $this->factory->create(ZipCodeType::class);

        $transformers = $form->getConfig()->getModelTransformers();
        $this->assertCount(1, $transformers);
        $this->assertInstanceOf(ZipCodeTransformer::class, $transformers[0]);

        $form->submit($submittedData);
        $this->assertEquals($valid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
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
     * {@inheritdoc}
     */
    protected function getValidators(): array
    {
        $zipCodeFieldsValidator = $this->getMockBuilder(ZipCodeFieldsValidator::class)
            ->onlyMethods(['initialize', 'isInteger'])
            ->getMock();
        $zipCodeFieldsValidator->expects($this->any())
            ->method('isInteger')
            ->willReturnCallback(function ($value) {
                return (bool)filter_var(str_replace('0', '', $value), FILTER_VALIDATE_INT);
            });
        $zipCodeFieldsValidator->expects($this->any())
            ->method('initialize')
            ->willReturnCallback(function (ExecutionContext $legacyContext) use ($zipCodeFieldsValidator) {
                $context = $this->createMock(ExecutionContextInterface::class);
                $builder = $this->createMock(ConstraintViolationBuilderInterface::class);

                $context->expects($this->any())
                    ->method('buildViolation')
                    ->with($this->isType('string'))
                    ->willReturnCallback(function ($message) use ($builder, $legacyContext) {
                        $constraint = new ZipCodeFields();
                        if ($message === $constraint->onlyNumericRangesSupported) {
                            $legacyContext->addViolation($constraint->onlyNumericRangesSupported);
                        }

                        return $builder;
                    });

                $builder->expects($this->any())
                    ->method('atPath')
                    ->with($this->isType('string'))
                    ->willReturn($builder);
                $builder->expects($this->any())
                    ->method('addViolation');

                ReflectionUtil::setPropertyValue($zipCodeFieldsValidator, 'context', $context);

                return true;
            });

        return [
            ZipCodeFieldsValidator::class => $zipCodeFieldsValidator
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ZipCodeType::class => $this->formType
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
