<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductVisibilitySearchQueryModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var AccountUserRelationsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $relationsProvider;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    protected $modifier;
    
    protected function setUp()
    {
        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);
        $this->relationsProvider = $this->getMockBuilder(AccountUserRelationsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->modifier = new ProductVisibilitySearchQueryModifier(
            $this->tokenStorage,
            $this->relationsProvider,
            $this->configManager
        );
    }

    public function testModify()
    {
        $accountUser = new AccountUser();
        $reflection = new \ReflectionProperty(AccountUser::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($accountUser, 1);

        $accountGroup = new AccountGroup();
        $reflection = new \ReflectionProperty(AccountGroup::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($accountGroup, 2);

        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->relationsProvider->expects($this->once())
            ->method('getAccountGroup')
            ->with($accountUser)
            ->willReturn($accountGroup);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_account.anonymous_account_group')
            ->willReturn(3);

        $query = new Query();
        $this->modifier->modify($query);

        /** @var CompositeExpression $expression */
        $expression = $query->getCriteria()->getWhereExpression();
        $this->assertNotNull($expression);

        $this->assertInstanceOf(CompositeExpression::class, $expression);
        $this->assertEquals(CompositeExpression::TYPE_OR, $expression->getType());
        $expressionList = $expression->getExpressionList();
        $this->assertCount(2, $expressionList);

        $first = reset($expressionList);
        $second = end($expressionList);

        $this->assertInstanceOf(CompositeExpression::class, $first);
        $this->assertEquals(CompositeExpression::TYPE_AND, $first->getType());
        $firstExpressionList = $first->getExpressionList();
        $this->assertCount(2, $firstExpressionList);

        $this->assertInstanceOf(CompositeExpression::class, $second);
        $this->assertEquals(CompositeExpression::TYPE_AND, $second->getType());
        $secondExpressionList = $second->getExpressionList();
        $this->assertCount(2, $secondExpressionList);

        $accountFirst = reset($firstExpressionList);
        $this->assertInstanceOf(Comparison::class, $accountFirst);
        $this->assertEquals('integer.visibility_account_1', $accountFirst->getField());
        $accountSecond = reset($secondExpressionList);
        $this->assertInstanceOf(Comparison::class, $accountSecond);
        $this->assertEquals('integer.visibility_account_1', $accountSecond->getField());

        $defaultFirst = end($firstExpressionList);
        $this->assertInstanceOf(Comparison::class, $defaultFirst);
        $this->assertEquals('integer.is_visible_by_default', $defaultFirst->getField());
        $defaultSecond = end($secondExpressionList);
        $this->assertInstanceOf(Comparison::class, $defaultSecond);
        $this->assertEquals('integer.is_visible_by_default', $defaultSecond->getField());
    }

    public function testModifyForAnonymous()
    {
        $accountUser = $accountGroup = null;

        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->relationsProvider->expects($this->once())
            ->method('getAccountGroup')
            ->with($accountUser)
            ->willReturn($accountGroup);

        $query = new Query();
        $this->modifier->modify($query);

        /** @var CompositeExpression $expression */
        $expression = $query->getCriteria()->getWhereExpression();
        $expressionList = $expression->getExpressionList();
        $first = reset($expressionList);
        $second = end($expressionList);

        $firstExpressionList = $first->getExpressionList();
        $secondExpressionList = $second->getExpressionList();

        $accountFirst = reset($firstExpressionList);
        $this->assertEquals('integer.visibility_anonymous', $accountFirst->getField());
        $accountSecond = reset($secondExpressionList);
        $this->assertEquals('integer.visibility_anonymous', $accountSecond->getField());

        $defaultFirst = end($firstExpressionList);
        $this->assertEquals('integer.is_visible_by_default', $defaultFirst->getField());
        $defaultSecond = end($secondExpressionList);
        $this->assertEquals('integer.is_visible_by_default', $defaultSecond->getField());
    }
}
