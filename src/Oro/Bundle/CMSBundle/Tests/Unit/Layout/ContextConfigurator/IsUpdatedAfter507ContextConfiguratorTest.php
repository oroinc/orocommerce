<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\ContextConfigurator;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Layout\ContextConfigurator\IsUpdatedAfter507ContextConfigurator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\LayoutContext;

/**
 * Component added back for theme layout BC from version 5.0
 */
class IsUpdatedAfter507ContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider contextDataProvider
     *
     * @param bool $expected
     * @param mixed $configValue
     */
    public function testConfigureContext($expected, $configValue)
    {
        $context = new LayoutContext();

        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::IS_UPDATED_AFTER_507))
            ->willReturn($configValue);

        $contextConfigurator = new IsUpdatedAfter507ContextConfigurator($configManager);
        $contextConfigurator->configureContext($context);

        $this->assertTrue($context->has('is_updated_after_507'));
        $this->assertEquals($expected, $context->get('is_updated_after_507'));
    }

    public function contextDataProvider(): array
    {
        return [
            'is upgraded from 5.0.7 or above' => [
                'expected' => true,
                'configValue' => true,
            ],
            'is upgraded from 5.0.6 or below' => [
                'expected' => false,
                'configValue' => false,
            ],
        ];
    }
}
