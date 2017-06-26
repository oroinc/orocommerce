<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Voter;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CheckoutBundle\Voter\GuestCheckoutVoter;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class GuestShoppingListVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VoterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configVoter;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * @var GuestCheckoutVoter
     */
    private $voter;

    protected function setUp()
    {
        $this->configVoter  = $this->createMock(VoterInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->voter        = new GuestCheckoutVoter($this->configVoter, $this->tokenStorage);
    }

    public function testVoteAbstain()
    {
        $vote = $this->voter->vote('some_feature');
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $vote);
    }

    public function testVoteEnabledForLoggedUser()
    {
        $scopeIdentifier = 1;
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $vote = $this->voter->vote(GuestCheckoutVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteEnabledForNotLoggedUser()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $scopeIdentifier = 1;
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with(GuestCheckoutVoter::FEATURE_NAME, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_ENABLED);

        $vote = $this->voter->vote(GuestCheckoutVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteDisabledForNotLoggedUser()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $scopeIdentifier = 1;
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with(GuestCheckoutVoter::FEATURE_NAME, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_DISABLED);

        $vote = $this->voter->vote(GuestCheckoutVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_DISABLED, $vote);
    }
}
