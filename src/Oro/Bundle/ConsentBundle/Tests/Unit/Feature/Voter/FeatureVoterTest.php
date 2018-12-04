<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Feature\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\Feature\Voter\FeatureVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

class FeatureVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var FeatureVoter */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->voter = new FeatureVoter($this->configManager, $this->frontendHelper);
    }
    
    public function testVoteAbstain()
    {
        $this->configManager->expects($this->never())
            ->method('get');

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $vote = $this->voter->vote('some_feature');
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $vote);
    }

    public function testVoteAbstainNotFrontendRequest()
    {
        $this->configManager->expects($this->never())
            ->method('get');

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->with(null)
            ->willReturn(false);

        $vote = $this->voter->vote(FeatureVoter::FEATURE_NAME);
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $vote);
    }
    
    public function testVoteEnabled()
    {
        $scopeIdentifier = 1;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_consent.enabled_consents', false, false, $scopeIdentifier)
            ->willReturn([1, 2]);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->with(null)
            ->willReturn(true);

        $vote = $this->voter->vote(FeatureVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }
    
    public function testVoteDisabled()
    {
        $scopeIdentifier = 1;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_consent.enabled_consents', false, false, $scopeIdentifier)
            ->willReturn([]);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->with(null)
            ->willReturn(true);

        $vote = $this->voter->vote(FeatureVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_DISABLED, $vote);
    }
}
