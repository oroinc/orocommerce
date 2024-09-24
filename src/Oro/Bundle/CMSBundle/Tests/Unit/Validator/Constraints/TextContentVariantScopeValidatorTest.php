<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CMSBundle\Validator\Constraints\TextContentVariantScope;
use Oro\Bundle\CMSBundle\Validator\Constraints\TextContentVariantScopeValidator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TextContentVariantScopeValidatorTest extends ConstraintValidatorTestCase
{
    public function testValidateEmpty(): void
    {
        $constraint = new TextContentVariantScope();
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateValid(): void
    {
        $scope = new Scope();
        $scope->setRowHash('any_scope_hash');

        $constraint = new TextContentVariantScope();
        $this->validator->validate($scope, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFail(): void
    {
        $scope = new Scope();
        $scope->setRowHash('default_scope_hash');

        $constraint = new TextContentVariantScope();
        $this->validator->validate($scope, $constraint);
        $this->buildViolation($constraint->message)->assertRaised();
    }

    #[\Override]
    protected function createValidator(): TextContentVariantScopeValidator
    {
        $scope = new Scope();
        $scope->setRowHash('default_scope_hash');

        $scopeManager = $this->createMock(ScopeManager::class);
        $scopeManager
            ->expects($this->any())
            ->method('findDefaultScope')
            ->willReturn($scope);

        return new TextContentVariantScopeValidator($scopeManager);
    }
}
