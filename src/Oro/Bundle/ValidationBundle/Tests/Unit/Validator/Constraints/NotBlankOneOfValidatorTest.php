<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOf;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOfValidator;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class NotBlankOneOfValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var NotBlankOneOf
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->validator = new NotBlankOneOfValidator(new PropertyAccessor());

        $this->validator->initialize($this->context);
    }

    public function testValidate()
    {
        
    }
}
