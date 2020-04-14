<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinksValidator;
use Symfony\Component\Validator\Constraint;

class EmptyVariantFieldInSimpleProductForVariantLinksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmptyVariantFieldInSimpleProductForVariantLinks
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
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
        $this->assertEquals(
            EmptyVariantFieldInSimpleProductForVariantLinksValidator::ALIAS,
            $this->constraint->validatedBy()
        );
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
