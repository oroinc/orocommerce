<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItem;

class LineItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LineItem
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new LineItem();
    }

    public function testGetTargets()
    {
        $this->assertEquals(LineItem::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $this->assertEquals('oro_shopping_list_line_item_validator', $this->constraint->validatedBy());
    }
}
