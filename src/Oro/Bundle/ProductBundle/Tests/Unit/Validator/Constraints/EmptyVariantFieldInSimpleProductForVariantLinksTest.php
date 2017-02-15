<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinksValidator;

class EmptyVariantFieldInSimpleProductForVariantLinksTest extends \PHPUnit_Framework_TestCase
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
