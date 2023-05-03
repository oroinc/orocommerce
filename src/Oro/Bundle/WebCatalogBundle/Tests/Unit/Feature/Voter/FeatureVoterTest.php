<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Feature\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Feature\Voter\FeatureVoter;

class FeatureVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FeatureVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->voter = new FeatureVoter($this->configManager);
    }

    public function testVoteAbstain()
    {
        $this->configManager->expects($this->never())
            ->method('get');

        $vote = $this->voter->vote('some_feature');
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $vote);
    }

    public function testVoteEnabled()
    {
        $scopeIdentifier = 1;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $scopeIdentifier)
            ->willReturn(null);

        $vote = $this->voter->vote(FeatureVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }

    public function testVoteDisabled()
    {
        $scopeIdentifier = 1;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $scopeIdentifier)
            ->willReturn(new WebCatalog());

        $vote = $this->voter->vote(FeatureVoter::FEATURE_NAME, $scopeIdentifier);
        $this->assertEquals(VoterInterface::FEATURE_DISABLED, $vote);
    }
}
