<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Provider\SearchEnginesProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchEnginesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getEnginesDataProvider
     *
     * @param bool $esExists
     * @param array $expected
     */
    public function testGetEngines($esExists, $expected)
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('has')
            ->willReturn($esExists);

        $provider = new SearchEnginesProvider($container);

        $this->assertEquals($expected, $provider->getEngines());
    }

    public function getEnginesDataProvider()
    {
        return [
            'ORM only' => [
                'esExists' => false,
                'expected' => [SearchEnginesProvider::SEARCH_ENGINE_ORM]
            ],
            'ORM and ES' => [
                'esExists' => true,
                'expected' => [SearchEnginesProvider::SEARCH_ENGINE_ORM, SearchEnginesProvider::SEARCH_ENGINE_ES]
            ]
        ];
    }
}
