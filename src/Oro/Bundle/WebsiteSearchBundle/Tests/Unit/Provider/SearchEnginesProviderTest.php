<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Provider\SearchEngineConfigProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchEngineConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getEnginesDataProvider
     *
     * @param string $method
     * @param string $key
     * @param string $value
     * @param array $expected
     */
    public function testGetEngines($method, $key, $value, $expected)
    {
        /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->with($key)
            ->willReturn($value);

        $provider = new SearchEngineConfigProvider($container);
        $result = call_user_func([$provider, $method]);

        $this->assertEquals($expected, $result);
    }

    public function getEnginesDataProvider()
    {
        return [
            'name' => [
                'method' => 'getEngineName',
                'key' => 'search_engine_name',
                'value' => 'ORM',
                'expected' => 'ORM'
            ],
            'host' => [
                'method' => 'getHost',
                'key' => 'search_engine_host',
                'value' => 'localhost',
                'expected' => 'localhost'
            ],
            'port' => [
                'method' => 'getPort',
                'key' => 'search_engine_port',
                'value' => '9000',
                'expected' => '9000'
            ],
            'username' => [
                'method' => 'getUsername',
                'key' => 'search_engine_username',
                'value' => 'username',
                'expected' => 'username'
            ],
            'password' => [
                'method' => 'getPassword',
                'key' => 'search_engine_password',
                'value' => 'password',
                'expected' => 'password'
            ],
            'auth_type' => [
                'method' => 'getAuthType',
                'key' => 'search_engine_auth_type',
                'value' => 'basic',
                'expected' => 'basic'
            ]
        ];
    }
}
