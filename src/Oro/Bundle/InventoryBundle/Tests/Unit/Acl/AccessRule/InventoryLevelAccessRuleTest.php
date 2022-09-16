<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\InventoryBundle\Acl\AccessRule\InventoryLevelAccessRule;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

class InventoryLevelAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    private InventoryLevelAccessRule $accessRule;

    protected function setUp(): void
    {
        $this->accessRule = new InventoryLevelAccessRule();
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess()
    {
        $criteria = new Criteria('ORM', InventoryLevel::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(new Association('product'), $criteria->getExpression());
    }
}
