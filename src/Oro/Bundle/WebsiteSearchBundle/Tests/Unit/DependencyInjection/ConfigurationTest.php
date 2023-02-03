<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private const ORO_WEBSITE_SEARCH_INDEXER_BATCH_SIZE_PATH = "oro_website_search.indexer_batch_size";

    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $builder);
    }

    public function testProcessConfiguration(): void
    {
        $processor = new Processor();
        $expected = [
            Configuration::ENGINE_KEY => SearchConfiguration::DEFAULT_ENGINE,
            Configuration::ENGINE_PARAMETERS_KEY => [],
            Configuration::INDEXER_BATCH_SIZE => Configuration::INDEXER_BATCH_SIZE_DEFAULT
        ];
        $this->assertEquals($expected, $processor->processConfiguration(new Configuration(), []));
    }

    public function testProcessConfigurationExceptionMax(): void
    {
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $valueOver = 101;
        $message = sprintf(
            "The value %d is too big for path \"%s\". Should be less than or equal to %d",
            $valueOver,
            self::ORO_WEBSITE_SEARCH_INDEXER_BATCH_SIZE_PATH,
            Configuration::INDEXER_BATCH_SIZE_MAX
        );
        $this->expectExceptionMessage($message);

        $processor->processConfiguration(
            new Configuration(),
            ['oro_website_search' => [Configuration::INDEXER_BATCH_SIZE => $valueOver]]
        );
    }

    public function testProcessConfigurationExceptionMin(): void
    {
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $valueLess = 0;
        $message = sprintf(
            "The value %d is too small for path \"%s\". Should be greater than or equal to %d",
            $valueLess,
            self::ORO_WEBSITE_SEARCH_INDEXER_BATCH_SIZE_PATH,
            Configuration::INDEXER_BATCH_SIZE_MIN
        );
        $this->expectExceptionMessage($message);

        $processor->processConfiguration(
            new Configuration(),
            ['oro_website_search' => [Configuration::INDEXER_BATCH_SIZE => $valueLess]]
        );
    }
}
