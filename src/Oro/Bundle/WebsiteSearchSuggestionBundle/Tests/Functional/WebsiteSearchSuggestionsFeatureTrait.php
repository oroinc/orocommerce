<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\WebsiteSearchSuggestionBundle\DependencyInjection\Configuration;

trait WebsiteSearchSuggestionsFeatureTrait
{
    use ConfigManagerAwareTestTrait;

    public function enableFeature(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::WEBSITE_SEARCH_SUGGESTION_FEATURE_ENABLED),
            true
        );
        $configManager->flush();

        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }
}
