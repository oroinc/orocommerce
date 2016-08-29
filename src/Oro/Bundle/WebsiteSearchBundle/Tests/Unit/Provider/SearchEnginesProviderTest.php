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
            ]
        ];
    }
}
