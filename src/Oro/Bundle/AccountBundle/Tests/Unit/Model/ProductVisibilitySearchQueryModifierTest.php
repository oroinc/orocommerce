<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductVisibilitySearchQueryModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    protected $modifier;
    
    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMockBuilder(TokenStorageInterface::class)
            ->getMock();

        $this->modifier = new ProductVisibilitySearchQueryModifier($this->tokenStorage);
    }

    public function testModify()
    {
        $accountUser = new AccountUser();
        $reflection = new \ReflectionProperty(AccountUser::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($accountUser, 1);

        $token = $this
            ->getMockBuilder(TokenInterface::class)
            ->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $query = new Query();
        $this->modifier->modify($query);

        /** @var CompositeExpression $expression */
        $expression = $query->getCriteria()->getWhereExpression();
        $expected = new CompositeExpression(
            CompositeExpression::TYPE_OR,
            [
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value(1)),
                        new Comparison('integer.visibility_account_1', Comparison::EQ, new Value(null)),
                    ]
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value(-1)),
                        new Comparison('integer.visibility_account_1', Comparison::EQ, new Value(1)),
                    ]
                ),
            ]
        );

        $this->assertNotNull($expression);
        $this->assertEquals($expected, $expression);
    }

    public function testModifyForAnonymous()
    {
        $accountUser = null;

        $token = $this
            ->getMockBuilder(TokenInterface::class)
            ->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $query = new Query();
        $this->modifier->modify($query);

        /** @var Comparison $expression */
        $expression = $query->getCriteria()->getWhereExpression();
        $expected = new Comparison('integer.visibility_anonymous', Comparison::EQ, new Value(1));

        $this->assertNotNull($expression);
        $this->assertEquals($expected, $expression);
    }
}
