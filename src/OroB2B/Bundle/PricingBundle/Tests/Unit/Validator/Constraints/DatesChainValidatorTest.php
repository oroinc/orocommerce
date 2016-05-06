<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Context\ExecutionContext;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\DatesChain;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\DatesChainValidator;

class DatesChainValidatorTest extends \PHPUnit_Framework_TestCase
{
    const FIRST_LABEL = 'First';
    const SECOND_LABEL = 'Second';
    const THIRD_LABEL = 'Third';
    const MESSAGE = 'orob2b.pricing.validators.price_list.dates_chain.message';

    /**
     * @dataProvider validateDataProvider
     *
     * @param object $value
     * @param array $violations
     */
    public function testValidate($value, array $violations)
    {
        $constraint = new DatesChain();
        $constraint->chain = [
            'first' => self::FIRST_LABEL,
            'second' => self::SECOND_LABEL,
            'third' => self::THIRD_LABEL,
        ];

        $context = $this->getContextMock();

        if (!$violations) {
            $context->expects($this->never())
                ->method('buildViolation');
        } else {
            $builder = $this->getBuilderMock();

            $builder->expects($this->any())
                ->method('atPath')
                ->willReturn($builder);

            foreach ($violations as $order => $violation) {
                $context->expects($this->at($order))
                    ->method('buildViolation')
                    ->with(self::MESSAGE, $violation)
                    ->willReturn($builder);
            }
        }

        $validator = new DatesChainValidator(new PropertyAccessor());
        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        $first = new \DateTime('2016-01-01');
        $second = new \DateTime('2016-01-02');
        $third = new \DateTime('2016-01-03');

        return [
            'valid chain' => [
                'value' => $this->createTestObject($first, $second, $third),
                'violations' => []
            ],
            'valid chain with null' => [
                'value' => $this->createTestObject($first, null, $third),
                'violations' => []
            ],
            'valid chain first null' => [
                'value' => $this->createTestObject(null, $second, $third),
                'violations' => []
            ],
            'not valid' => [
                'value' => $this->createTestObject($third, $second, $first),
                'violations' => [
                    ['invalid' => self::SECOND_LABEL, 'comparedWith' => self::FIRST_LABEL],
                    ['invalid' => self::THIRD_LABEL, 'comparedWith' => self::SECOND_LABEL]
                ]
            ],
            'not valid with null' => [
                'value' => $this->createTestObject($second, null, $first),
                'violations' => [
                    ['invalid' => self::THIRD_LABEL, 'comparedWith' => self::FIRST_LABEL]
                ]
            ]
        ];
    }

    /**
     * @param null|\DateTime $first
     * @param null|\DateTime $second
     * @param null|\DateTime $third
     * @return \stdClass
     */
    protected function createTestObject($first, $second, $third)
    {
        $result = new \stdClass();
        $result->first = $first;
        $result->second = $second;
        $result->third = $third;

        return $result;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context
     */
    protected function getContextMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
