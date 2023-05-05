<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessConfiguration(): void
    {
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new Configuration(), []);

        $this->assertEquals(
            [
                Configuration::ENGINE_KEY_DSN => SearchConfiguration::DEFAULT_ENGINE_DSN,
                Configuration::ENGINE_PARAMETERS_KEY => [],
                Configuration::INDEXER_BATCH_SIZE => Configuration::INDEXER_BATCH_SIZE_DEFAULT,
                'settings' => [
                    'resolved' => true,
                    'enable_global_search_history_feature' => ['value' => false, 'scope' => 'app'],
                    'enable_global_search_history_tracking' => ['value' => true, 'scope' => 'app'],
                ],
            ],
            $processedConfig
        );
    }

    public function testProcessConfigurationExceptionMax(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The value 101 is too big for path "oro_website_search.indexer_batch_size".'
            .' Should be less than or equal to 100'
        );

        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            ['oro_website_search' => [Configuration::INDEXER_BATCH_SIZE => 101]]
        );
    }

    public function testProcessConfigurationExceptionMin(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The value 0 is too small for path "oro_website_search.indexer_batch_size".'
            .' Should be greater than or equal to 1'
        );

        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            ['oro_website_search' => [Configuration::INDEXER_BATCH_SIZE => 0]]
        );
    }
}
