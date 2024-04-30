<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WebsiteSearchSuggestionBundle\DependencyInjection\Configuration;

final class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConfigIsCorrect(): void
    {
        $config = new Configuration();
        $finalizedConfig = $config->getConfigTreeBuilder()->buildTree()->finalize([]);
        $settings = $finalizedConfig['settings'];

        self::assertArrayHasKey('search_autocomplete_max_suggests', $settings);
        self::assertArrayHasKey('website_search_suggestion_feature_enabled', $settings);
        self::assertEquals(4, $settings['search_autocomplete_max_suggests']['value']);
        self::assertTrue($settings['website_search_suggestion_feature_enabled']['value']);
    }

    public function testThatConfigReturnsCorrectPath(): void
    {
        self::assertEquals(
            'oro_website_search_suggestion.special_key',
            Configuration::getConfigKeyByName('special_key')
        );
    }
}
