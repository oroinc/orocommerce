<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\ShoppingListBundle\Acl\AccessRule\LineItemAssociationAwareAccessRule;
use PHPUnit\Framework\TestCase;

final class LineItemAssociationAwareAccessRuleTest extends TestCase
{
    private LineItemAssociationAwareAccessRule $accessRule;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessRule = new LineItemAssociationAwareAccessRule('association', 'association2');
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess(): void
    {
        $criteria = new Criteria('ORM', \stdClass::class, 'test');
        $this->accessRule->process($criteria);

        $expected = new CompositeExpression(CompositeExpression::TYPE_OR, [
            new Association('association'),
            new Association('association2')
        ]);

        self::assertEquals($expected, $criteria->getExpression());
    }
}
