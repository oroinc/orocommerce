<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOf;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class BlankOneOfTest extends TestCase
{
    public function testGetTargets()
    {
        $constraint = new BlankOneOf();

        static::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testMessage()
    {
        $constraint = new BlankOneOf();

        static::assertSame('One of fields: %fields% should be blank', $constraint->message);
    }
}
