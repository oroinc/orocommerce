<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

use OroB2B\Bundle\FrontendBundle\EventListener\FrontendRouteCollectionListener;

class FrontendRouteCollectionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param RouteCollection $collection
     * @param array $expected
     *
     * @dataProvider dataProvider
     */
    public function testOnCollectionAutoload(RouteCollection $collection, array $expected)
    {
        $listener = new FrontendRouteCollectionListener(['route_should_be_frontend']);

        $event = new RouteCollectionEvent($collection);
        $listener->onCollectionAutoload($event);

        $this->assertEquals($expected, $event->getCollection()->getIterator()->getArrayCopy());
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                $this->getCollection(['route_should_be_frontend' => new Route('/route1')]),
                ['route_should_be_frontend' => (new Route('/route1'))->setOption('frontend', true)]
            ],
            [
                $this->getCollection(['route_should_not_be_frontend' => new Route('/route2')]),
                ['route_should_not_be_frontend' => new Route('/route2')]
            ]

        ];
    }

    /**
     * @param Route[] $routes
     *
     * @return RouteCollection
     */
    protected function getCollection(array $routes)
    {
        $collection = new RouteCollection();

        foreach ($routes as $routeName => $route) {
            $collection->add($routeName, $route);
        }

        return $collection;
    }
}
