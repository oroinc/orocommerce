<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

class ActionConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider */
    protected $cacheProvider;

    protected function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['contains', 'fetch', 'save', 'deleteAll'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    protected function tearDown()
    {
        unset($this->cacheProvider);
    }

    public function testGetActionConfigurationWithCache()
    {
        $config = ['test' => 'config'];

        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(true);
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn($config);

        $configurationProvider = new ActionConfigurationProvider($this->cacheProvider, [], []);

        $this->assertEquals($config, $configurationProvider->getActionConfiguration());
    }

    public function testGetActionConfigurationWithoutCache()
    {
        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(false);
        $this->cacheProvider->expects($this->never())
            ->method('fetch')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME);
        $this->cacheProvider->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(true);

        $configurationProvider = new ActionConfigurationProvider(
            $this->cacheProvider,
            [
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle3' => [
                    'test_action3' => [],
                    'test_action4' => []
                ],
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1' => [
                    'test_action1' => [],
                    'test_action2' => []
                ],
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle2' => [
                    'test_action5' => []
                ]
            ],
            [
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle3',
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1',
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle2'
            ]
        );

        $this->assertInternalType('array', $configurationProvider->getActionConfiguration());
    }
}
