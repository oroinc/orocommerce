<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinksValidator;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinks;

class ProductVariantLinksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVariantLinks
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductVariantLinks();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->constraint);
    }

    public function testValidatedBy()
    {
        $this->assertEquals(ProductVariantLinksValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
