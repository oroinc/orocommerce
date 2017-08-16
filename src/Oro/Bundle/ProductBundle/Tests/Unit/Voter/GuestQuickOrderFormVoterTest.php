<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ProductBundle\Voter\GuestQuickOrderFormVoter;

class GuestQuickOrderFormVoterTest extends \PHPUnit_Framework_TestCase
{
    /** @var VoterInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $configVoter;

    /** @var GuestQuickOrderFormVoter */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configVoter = $this->createMock(VoterInterface::class);
        $this->voter = new GuestQuickOrderFormVoter($this->configVoter);
    }

    public function testVoteAbstainForAnotherFeature()
    {
        $vote = $this->voter->vote('some_feature');
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $vote);
    }

    public function testVoteEnabled()
    {
        $featureName = 'feature_name';

        $scopeIdentifier = 1;
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with($featureName, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_ENABLED);

        $this->voter->setFeatureName($featureName);

        $vote = $this->voter->vote('guest_quick_order_form', $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteDisabled()
    {
        $featureName = 'feature_name';

        $scopeIdentifier = 1;
        $this->configVoter->expects($this->once())
            ->method('vote')
            ->with($featureName, $scopeIdentifier)
            ->willReturn(VoterInterface::FEATURE_DISABLED);

        $this->voter->setFeatureName($featureName);

        $vote = $this->voter->vote('guest_quick_order_form', $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_DISABLED, $vote);
    }
}
