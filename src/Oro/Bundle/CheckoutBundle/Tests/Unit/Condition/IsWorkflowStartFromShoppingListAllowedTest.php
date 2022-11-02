<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class IsWorkflowStartFromShoppingListAllowedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

    /**
     * @var IsWorkflowStartFromShoppingListAllowed
     */
    private $condition;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->tokenStorage   = $this->createMock(TokenStorageInterface::class);
        $this->condition      = new IsWorkflowStartFromShoppingListAllowed($this->featureChecker, $this->tokenStorage);
    }

    /**
     * @param string $tokenClass
     */
    private function configureToken($tokenClass)
    {
        $token = $this->createMock($tokenClass);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
    }

    public function testIsAllowedForLoggedDefault()
    {
        $this->assertTrue($this->condition->isAllowedForLogged());
    }

    public function testIsAllowedForLoggedFalse()
    {
        $this->configureToken(AnonymousCustomerUserToken::class);
        $this->assertFalse($this->condition->isAllowedForLogged());
    }

    public function testIsAllowedForAnyLoggedUser()
    {
        $this->configureToken(\stdClass::class);
        $this->assertTrue($this->condition->isAllowedForLogged());
    }

    public function testIsAllowedForAnyGuestFeatureEnabled()
    {
        $this->configureToken(AnonymousCustomerUserToken::class);
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_checkout', null)
            ->willReturn(true);
        $this->assertTrue($this->condition->isAllowedForAny());
    }

    public function testIsAllowedForAnyGuestFeatureDisabled()
    {
        $this->configureToken(AnonymousCustomerUserToken::class);
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_checkout', null)
            ->willReturn(false);
        $this->assertFalse($this->condition->isAllowedForAny());
    }

    public function testIsAllowedForGuestFalseAsDefault()
    {
        $this->configureToken(\stdClass::class);
        $this->assertFalse($this->condition->isAllowedForGuest());
    }
}
