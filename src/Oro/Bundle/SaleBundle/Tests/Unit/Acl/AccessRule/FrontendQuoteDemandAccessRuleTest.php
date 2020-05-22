<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SaleBundle\Acl\AccessRule\FrontendQuoteDemandAccessRule;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendQuoteDemandAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var FrontendQuoteDemandAccessRule */
    private $accessRule;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->accessRule = new FrontendQuoteDemandAccessRule($this->tokenStorage);
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcessWhenNoSecurityToken()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertNull($criteria->getExpression());
    }

    public function testProcessForVisitor()
    {
        $visitor = $this->getEntity(CustomerVisitor::class, ['id' => 2]);
        $token = new AnonymousCustomerUserToken('', [], $visitor);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(
            new Comparison(new Path('visitor'), Comparison::EQ, 2),
            $criteria->getExpression()
        );
    }

    public function testProcessForCustomerUser()
    {
        $user = $this->getEntity(CustomerUser::class, ['id' => 30]);
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(
            new Comparison(new Path('customerUser'), Comparison::EQ, 30),
            $criteria->getExpression()
        );
    }

    public function testProcessWhenNoUser()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertNull($criteria->getExpression());
    }
}
