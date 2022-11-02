<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\OrderBundle\Acl\AccessRule\OrderLineItemAccessRule;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

class OrderLineItemAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderLineItemAccessRule */
    private $accessRule;

    protected function setUp(): void
    {
        $this->accessRule = new OrderLineItemAccessRule();
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess()
    {
        $criteria = new Criteria('ORM', OrderLineItem::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(
            new Association('order'),
            $criteria->getExpression()
        );
    }
}
