<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class IsWorkflowStartFromShoppingListAllowedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $featureChecker;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * @var IsWorkflowStartFromShoppingListAllowed
     */
    private $condition;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->tokenStorage   = $this->createMock(TokenStorageInterface::class);
        $this->condition      = new IsWorkflowStartFromShoppingListAllowed($this->featureChecker, $this->tokenStorage);
    }

    public function testAllowed()
    {
        $this->assertTrue($this->condition->isAllowed());
    }

    public function testAllowedForAnonymousUser()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_checkout', null)
            ->willReturn(true);

        $this->assertTrue($this->condition->isAllowed());
    }

    public function testNotAllowedForAnonymousUser()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_checkout', null)
            ->willReturn(false);

        $this->assertFalse($this->condition->isAllowed());
    }
}
