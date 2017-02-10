<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ShoppingListBundle\Validator\Constraints\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\MatrixCollectionColumnValidator;

class MatrixCollectionColumnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MatrixCollectionColumn
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new MatrixCollectionColumn();
    }

    public function testGetTargets()
    {
        $this->assertEquals(MatrixCollectionColumn::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $this->assertEquals(MatrixCollectionColumnValidator::class, $this->constraint->validatedBy());
    }
}
