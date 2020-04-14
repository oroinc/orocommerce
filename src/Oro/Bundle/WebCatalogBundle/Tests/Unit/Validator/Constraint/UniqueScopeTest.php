<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueScope;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueScopeValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueScopeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var UniqueScopeValidator
     */
    protected $validator;

    /**
     * @var UniqueScope
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new UniqueScope();

        $this->validator = new UniqueScopeValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateNull()
    {
        $value = null;
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateEmptyCollection()
    {
        $value = new ArrayCollection();
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateValid()
    {
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);
        $values = [
            (new ContentVariant())->addScope($scope1),
            (new ContentVariant())->addScope($scope2),
        ];
        $value = new ArrayCollection($values);
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateValidWithDefault()
    {
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);
        $values = [
            (new ContentVariant())->setDefault(true)->addScope($scope1),
            (new ContentVariant())->addScope($scope2)->addScope($scope1),
        ];
        $value = new ArrayCollection($values);
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateInvalid()
    {
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);
        $values = [
            (new ContentVariant())->addScope($scope1),
            (new ContentVariant())->addScope($scope2)->addScope($scope1),
        ];
        $value = new ArrayCollection($values);
        $constraintBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintBuilder->expects($this->once())
            ->method('atPath')
            ->with('[1].scopes[1]')
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
