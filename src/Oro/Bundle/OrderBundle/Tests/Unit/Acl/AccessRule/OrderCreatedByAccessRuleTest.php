<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\OrderBundle\Acl\AccessRule\OrderCreatedByAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\ExpressionInterface;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class OrderCreatedByAccessRuleTest extends TestCase
{
    private OrderCreatedByAccessRule $accessRule;

    protected function setUp(): void
    {
        $this->accessRule = new OrderCreatedByAccessRule();
    }

    /**
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(
        array $options,
        string $alias,
        string $permission,
        ExpressionInterface $expression,
        bool $result
    ) {
        $criteria = new Criteria('ORM', User::class, $alias, $permission);
        $criteria->setOption(AclAccessRule::CONDITION_DATA_BUILDER_CONTEXT, $options);
        $criteria->setExpression($expression);

        $this->assertEquals(
            $result,
            $this->accessRule->isApplicable($criteria)
        );
    }

    public function isApplicableProvider(): array
    {
        return [
            'no option, no alias, no permission, no access denied expression' => [
                'options' => [],
                'alias' => 'owner',
                'permission' => BasicPermission::CREATE,
                'expression' => new Association('order'),
                'result' => false,
            ],
            'option, no alias, no permission, no access denied expression' => [
                'options' => ['override_created_by_acl' => true],
                'alias' => 'owner',
                'permission' => BasicPermission::EDIT,
                'expression' => new Association('order'),
                'result' => false,
            ],
            'option, alias, no permission, no access denied expression' => [
                'options' => ['override_created_by_acl' => true],
                'alias' => 'created_by',
                'permission' => BasicPermission::DELETE,
                'expression' => new Association('order'),
                'result' => false,
            ],
            'option, no alias, permission, no access denied expression' => [
                'options' => ['override_created_by_acl' => true],
                'alias' => 'owner',
                'permission' => BasicPermission::VIEW,
                'expression' => new Association('order'),
                'result' => false,
            ],
            'no option, no alias, no permission, access denied expression' => [
                'options' => [],
                'alias' => 'owner',
                'permission' => BasicPermission::EDIT,
                'expression' => new AccessDenied(),
                'result' => false,
            ],
            'option, alias, permission, access denied expression' => [
                'options' => ['override_created_by_acl' => true],
                'alias' => 'created_by',
                'permission' => BasicPermission::VIEW,
                'expression' => new AccessDenied(),
                'result' => true,
            ],
        ];
    }

    public function testProcess()
    {
        $criteria = new Criteria('ORM', User::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(
            new Comparison(true, Comparison::EQ, true),
            $criteria->getExpression()
        );
    }
}
