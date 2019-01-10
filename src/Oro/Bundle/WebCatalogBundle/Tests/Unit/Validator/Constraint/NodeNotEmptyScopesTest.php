<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeNotEmptyScopes;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeNotEmptyScopesValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NodeNotEmptyScopesTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var NodeNotEmptyScopesValidator
     */
    protected $validator;

    /**
     * @var NodeNotEmptyScopes
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new NodeNotEmptyScopes();

        $this->validator = new NodeNotEmptyScopesValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateUnsupported()
    {
        $value = null;
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateValid()
    {
        $value = new ContentNode();
        $scope = new Scope();
        $value->addScope($scope);

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateInvalid()
    {
        $value = new ContentNode();

        $constraintBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintBuilder->expects($this->once())
            ->method('atPath')
            ->with('scopes')
            ->willReturnSelf();
        $constraintBuilder->expects($this->once())
            ->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($constraintBuilder);

        $this->validator->validate($value, $this->constraint);
    }
}
