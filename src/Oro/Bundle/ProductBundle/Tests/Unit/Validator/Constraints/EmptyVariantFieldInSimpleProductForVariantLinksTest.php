<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

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
    protected function setUp()
    {
        $this->constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
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
