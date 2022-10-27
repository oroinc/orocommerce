<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Feature;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\PricingBundle\Feature\PricingStorageVoter;
use PHPUnit\Framework\MockObject\MockObject;

class PricingStorageVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|MockObject
     */
    private $configManager;

    /**
     * @var PricingStorageVoter
     */
    private $voter;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->voter = new PricingStorageVoter($this->configManager);
    }

    /**
     * @dataProvider voterDataProvider
     * @param string $storage
     * @param string $feature
     * @param int $expected
     */
    public function testVote($storage, $feature, $expected)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_pricing.price_storage')
            ->willReturn($storage);

        $this->assertSame($expected, $this->voter->vote($feature));
    }

    /**
     * @return array[]
     */
    public function voterDataProvider(): array
    {
        return [
            'flat flat' => [
                'flat',
                'oro_price_lists_flat',
                VoterInterface::FEATURE_ENABLED
            ],
            'flat combined' => [
                'flat',
                'oro_price_lists_combined',
                VoterInterface::FEATURE_DISABLED
            ],
            'combined combined' => [
                'combined',
                'oro_price_lists_flat',
                VoterInterface::FEATURE_DISABLED
            ],
            'combined flat' => [
                'combined',
                'oro_price_lists_combined',
                VoterInterface::FEATURE_ENABLED
            ],
            'flat some' => [
                'flat',
                'some_feature',
                VoterInterface::FEATURE_ABSTAIN
            ],
            'combined some' => [
                'combined',
                'some_feature',
                VoterInterface::FEATURE_ABSTAIN
            ]
        ];
    }
}
