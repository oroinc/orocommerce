<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ProductBundle\Voter\ProductOriginalFileNamesConfigurationVoter;

class ProductOriginalFileNamesConfigurationVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider featuresDataProvider
     */
    public function testVotingResults(
        string $feature,
        bool $isAttachmentOriginalFileNamesFeatureEnabled,
        int $expectedVotingResult
    ): void {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('attachment_original_filenames')
            ->willReturn($isAttachmentOriginalFileNamesFeatureEnabled);

        $voter = new ProductOriginalFileNamesConfigurationVoter();
        $voter->setFeatureChecker($featureChecker);
        $voter->addFeature('attachment_original_filenames');

        self::assertEquals($expectedVotingResult, $voter->vote($feature));
    }

    public function featuresDataProvider(): array
    {
        return [
            "Not 'product_original_filenames_configuration' feature + 'attachment_original_filenames' disabled" =>
                ['not_product_original_filenames_configuration', false, VoterInterface::FEATURE_ABSTAIN],
            "Not 'product_original_filenames_configuration' feature + 'attachment_original_filenames' enabled" =>
                ['not_product_original_filenames_configuration', true, VoterInterface::FEATURE_ABSTAIN],
            "'product_original_filenames_configuration' feature + 'attachment_original_filenames' disabled" =>
                ['product_original_filenames_configuration', false, VoterInterface::FEATURE_ENABLED],
            "'product_original_filenames_configuration' feature + `attachment_original_filenames` enabled" =>
                ['product_original_filenames_configuration', true, VoterInterface::FEATURE_DISABLED],
        ];
    }
}
