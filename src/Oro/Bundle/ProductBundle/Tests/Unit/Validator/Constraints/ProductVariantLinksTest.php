<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinksValidator;
use Symfony\Component\Validator\Constraint;

class ProductVariantLinksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductVariantLinks
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new ProductVariantLinks();
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
        $this->assertEquals(ProductVariantLinksValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(
            [Constraint::CLASS_CONSTRAINT, Constraint::PROPERTY_CONSTRAINT],
            $this->constraint->getTargets()
        );
    }
}
