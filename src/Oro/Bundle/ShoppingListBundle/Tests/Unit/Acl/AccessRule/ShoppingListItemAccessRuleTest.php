<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\ShoppingListBundle\Acl\AccessRule\ShoppingListItemAccessRule;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class ShoppingListItemAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListItemAccessRule */
    private $accessRule;

    protected function setUp(): void
    {
        $this->accessRule = new ShoppingListItemAccessRule();
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess()
    {
        $criteria = new Criteria('ORM', LineItem::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(
            new Association('shoppingList'),
            $criteria->getExpression()
        );
    }
}
