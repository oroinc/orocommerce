<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;
use Symfony\Component\Validator\Constraint;

class UniqueProductVariantLinksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UniqueProductVariantLinks
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new UniqueProductVariantLinks();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->constraint);
    }

    public function testValidatedBy()
    {
        $this->assertEquals(UniqueProductVariantLinksValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(
            [Constraint::CLASS_CONSTRAINT, Constraint::PROPERTY_CONSTRAINT],
            $this->constraint->getTargets()
        );
    }
}
