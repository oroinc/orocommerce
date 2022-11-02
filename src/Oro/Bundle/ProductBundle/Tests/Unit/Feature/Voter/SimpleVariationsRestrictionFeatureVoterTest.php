<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Feature\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Feature\Voter\SimpleVariationsRestrictionFeatureVoter;
use PHPUnit\Framework\MockObject\MockObject;

class SimpleVariationsRestrictionFeatureVoterTest extends \PHPUnit\Framework\TestCase
{
    private ConfigManager|MockObject $configManager;
    private SimpleVariationsRestrictionFeatureVoter $voter;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->voter = new SimpleVariationsRestrictionFeatureVoter($this->configManager);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(string $feature, string $configValue, int $expected)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_product.display_simple_variations')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->voter->vote($feature));
    }

    public function voteDataProvider(): \Generator
    {
        yield ['some_feature', 'hide_completely', SimpleVariationsRestrictionFeatureVoter::FEATURE_ABSTAIN];
        yield [
            'simple_variations_view_restriction',
            'hide_completely',
            SimpleVariationsRestrictionFeatureVoter::FEATURE_ENABLED
        ];
        yield [
            'simple_variations_view_restriction',
            'hide_catalog',
            SimpleVariationsRestrictionFeatureVoter::FEATURE_DISABLED
        ];
        yield [
            'simple_variations_view_restriction',
            'everywhere',
            SimpleVariationsRestrictionFeatureVoter::FEATURE_DISABLED
        ];
    }
}
