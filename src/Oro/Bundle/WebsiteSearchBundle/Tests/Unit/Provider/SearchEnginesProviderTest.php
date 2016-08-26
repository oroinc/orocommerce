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
            ->method('getParameter')
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
                'key' => 'oro_website_search.engine_name',
                'value' => 'orm',
                'expected' => 'orm'
            ],
            'host' => [
                'method' => 'getHost',
                'key' => 'oro_website_search.engine_parameters.host',
                'value' => 'localhost',
                'expected' => 'localhost'
            ],
            'port' => [
                'method' => 'getPort',
                'key' => 'oro_website_search.engine_parameters.port',
                'value' => '9000',
                'expected' => '9000'
            ],
            'username' => [
                'method' => 'getUsername',
                'key' => 'oro_website_search.engine_parameters.username',
                'value' => 'username',
                'expected' => 'username'
            ],
            'password' => [
                'method' => 'getPassword',
                'key' => 'oro_website_search.engine_parameters.password',
                'value' => 'password',
                'expected' => 'password'
            ],
            'auth_type' => [
                'method' => 'getAuthType',
                'key' => 'oro_website_search.engine_parameters.auth_type',
                'value' => 'basic',
                'expected' => 'basic'
            ]
        ];
    }
}
