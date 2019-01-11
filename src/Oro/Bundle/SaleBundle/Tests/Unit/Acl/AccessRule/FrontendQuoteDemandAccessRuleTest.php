<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Acl\AccessRule\FrontendQuoteDemandAccessRule;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class FrontendQuoteDemandAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MockObject */
    private $frontendHelper;

    /** @var MockObject */
    private $tokenAccessor;

    /** @var FrontendQuoteDemandAccessRule */
    private $accessRule;

    protected function setUp()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);

        $this->accessRule = new FrontendQuoteDemandAccessRule($this->frontendHelper, $this->tokenAccessor);
    }

    public function testIsApplicableOnNonSuppotredEntity()
    {
        $criteria = new Criteria('ORM', \stdClass::class, 'test');

        $this->assertFalse($this->accessRule->isApplicable($criteria));
    }

    public function testIsApplicableOnNonFrontendRequest()
    {
        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->assertFalse($this->accessRule->isApplicable($criteria));
    }

    public function testIsApplicable()
    {
        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->assertTrue($this->accessRule->isApplicable($criteria));
    }

    public function testProcessWithVisitor()
    {
        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');

        $visitor = $this->getEntity(CustomerVisitor::class, ['id' => 2]);

        $token = new AnonymousCustomerUserToken('', [], $visitor);

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->accessRule->process($criteria);

        $this->assertEquals(
            new Comparison(new Path('visitor'), Comparison::EQ, 2),
            $criteria->getExpression()
        );
    }

    public function testProcessWithCustomerUser()
    {
        $criteria = new Criteria('ORM', QuoteDemand::class, 'test');

        $user = $this->getEntity(User::class, ['id' => 30]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->accessRule->process($criteria);

        $this->assertEquals(
            new Comparison(new Path('customerUser'), Comparison::EQ, 30),
            $criteria->getExpression()
        );
    }
}
