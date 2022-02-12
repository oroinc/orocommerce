<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeNotEmptyScopes;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeNotEmptyScopesValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NodeNotEmptyScopesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NodeNotEmptyScopesValidator
    {
        return new NodeNotEmptyScopesValidator();
    }

    public function testGetTargets()
    {
        $constraint = new NodeNotEmptyScopes();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateUnsupported()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(null, new NodeNotEmptyScopes());
    }

    public function testValidateValid()
    {
        $value = new ContentNode();
        $value->addScope(new Scope());

        $constraint = new NodeNotEmptyScopes();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateInvalid()
    {
        $value = new ContentNode();

        $constraint = new NodeNotEmptyScopes();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('property.path.scopes')
            ->assertRaised();
    }
}
