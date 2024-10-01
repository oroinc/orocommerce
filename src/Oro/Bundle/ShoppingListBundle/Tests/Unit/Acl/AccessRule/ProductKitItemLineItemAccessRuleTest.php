<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\ShoppingListBundle\Acl\AccessRule\ProductKitItemLineItemAccessRule;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use PHPUnit\Framework\TestCase;

class ProductKitItemLineItemAccessRuleTest extends TestCase
{
    private ProductKitItemLineItemAccessRule $accessRule;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessRule = new ProductKitItemLineItemAccessRule();
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess(): void
    {
        $criteria = new Criteria('ORM', ProductKitItemLineItem::class, 'test');
        $this->accessRule->process($criteria);

        self::assertEquals(
            new Association('lineItem'),
            $criteria->getExpression()
        );
    }
}
