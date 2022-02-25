<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueScope;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\UniqueScopeValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueScopeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): UniqueScopeValidator
    {
        return new UniqueScopeValidator();
    }

    private function getScope(int $id): Scope
    {
        $scope = new Scope();
        ReflectionUtil::setId($scope, $id);

        return $scope;
    }

    public function testValidateNull()
    {
        $constraint = new UniqueScope();
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateEmptyCollection()
    {
        $constraint = new UniqueScope();
        $this->validator->validate(new ArrayCollection(), $constraint);
        $this->assertNoViolation();
    }

    public function testValidateValid()
    {
        $scope1 = $this->getScope(1);
        $scope2 = $this->getScope(2);
        $values = [
            (new ContentVariant())->addScope($scope1),
            (new ContentVariant())->addScope($scope2),
        ];

        $constraint = new UniqueScope();
        $this->validator->validate(new ArrayCollection($values), $constraint);
        $this->assertNoViolation();
    }

    public function testValidateValidWithDefault()
    {
        $scope1 = $this->getScope(1);
        $scope2 = $this->getScope(2);
        $values = [
            (new ContentVariant())->setDefault(true)->addScope($scope1),
            (new ContentVariant())->addScope($scope2)->addScope($scope1),
        ];

        $constraint = new UniqueScope();
        $this->validator->validate(new ArrayCollection($values), $constraint);
        $this->assertNoViolation();
    }

    public function testValidateInvalid()
    {
        $scope1 = $this->getScope(1);
        $scope2 = $this->getScope(2);
        $values = [
            (new ContentVariant())->addScope($scope1),
            (new ContentVariant())->addScope($scope2)->addScope($scope1),
        ];

        $constraint = new UniqueScope();
        $this->validator->validate(new ArrayCollection($values), $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path[1].scopes[1]')
            ->assertRaised();
    }
}
