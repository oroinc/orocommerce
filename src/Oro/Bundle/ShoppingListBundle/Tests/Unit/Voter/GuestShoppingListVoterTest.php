<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Feature\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ShoppingListBundle\Voter\GuestShoppingListVoter;

class GuestShoppingListVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigVoter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configVoter;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var GuestShoppingListVoter
     */
    private $voter;

    protected function setUp()
    {
        $this->configVoter    = $this->createMock(ConfigVoter::class);
        $this->securityFacade = $this->createMock(SecurityFacade::class);
        $this->voter          = new GuestShoppingListVoter($this->configVoter, $this->securityFacade);
    }

    public function testVoteAbstain()
    {
        $vote = $this->voter->vote('some_feature');
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $vote);
    }

    public function testVoteEnabledForLoggedUser()
    {
        $scopeIdentifier = 1;
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->willReturn(true);

        $vote = $this->voter->vote(GuestShoppingListVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteEnabledForNotLoggedUser()
    {
        $scopeIdentifier = 1;
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->willReturn(false);
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with(GuestShoppingListVoter::FEATURE_NAME, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_ENABLED);

        $vote = $this->voter->vote(GuestShoppingListVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteDisabledForNotLoggedUser()
    {
        $scopeIdentifier = 1;
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->willReturn(false);
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with(GuestShoppingListVoter::FEATURE_NAME, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_DISABLED);

        $vote = $this->voter->vote(GuestShoppingListVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_DISABLED, $vote);
    }
}
