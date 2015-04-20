<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Config;

use Oro\Bundle\ApplicationBundle\Config\ConfigManager;

class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = new ConfigManager();
    }

    /**
     * Test getter
     */
    public function testGet()
    {
        $this->assertEquals('open', $this->configManager->get('oro_b2b_rfp_admin.default_request_status'));
        $this->assertNull($this->configManager->get('nonexistent_config'));
    }
}
