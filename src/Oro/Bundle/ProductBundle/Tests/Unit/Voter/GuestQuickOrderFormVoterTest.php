<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Voter;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ProductBundle\Voter\GuestQuickOrderFormVoter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GuestQuickOrderFormVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var VoterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configVoter;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var GuestQuickOrderFormVoter */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configVoter = $this->createMock(VoterInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->voter = new GuestQuickOrderFormVoter($this->configVoter, $this->tokenStorage, 'guest_quick_order');
    }

    public function testVoteEnabledForNotAnonymousUser()
    {
        $featureName = 'feature_name';

        $token = new \stdClass();
        $scopeIdentifier = 1;
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->voter->setFeatureName($featureName);

        $vote = $this->voter->vote('guest_quick_order', $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteAbstainForAnotherFeature()
    {
        $vote = $this->voter->vote('some_feature');
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $vote);
    }

    public function testVoteEnabled()
    {
        $token = new AnonymousCustomerUserToken('');
        $featureName = 'feature_name';

        $scopeIdentifier = 1;
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with($featureName, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_ENABLED);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->voter->setFeatureName($featureName);

        $vote = $this->voter->vote('guest_quick_order', $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteDisabled()
    {
        $token = new AnonymousCustomerUserToken('');
        $featureName = 'feature_name';

        $scopeIdentifier = 1;
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with($featureName, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_DISABLED);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->voter->setFeatureName($featureName);

        $vote = $this->voter->vote('guest_quick_order', $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_DISABLED, $vote);
    }
}
