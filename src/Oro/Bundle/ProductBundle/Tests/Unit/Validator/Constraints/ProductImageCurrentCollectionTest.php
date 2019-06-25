<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageCurrentCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class ProductImageCurrentCollectionTest extends TestCase
{
    public function testGetTargets()
    {
        $constraint = new ProductImageCurrentCollection();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
