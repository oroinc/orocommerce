<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Oro\Bundle\ShoppingListBundle\Condition\RfpRouteExists;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RfpRouteExistsTest extends \PHPUnit_Framework_TestCase
{
    const PROPERTY_PATH_NAME = 'testPropertyPath';

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var RfpRouteExists
     */
    protected $rfpRouteExists;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    /**
     * @var RouteCollection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $routeCollection;

    protected function setUp()
    {
        $this->routeCollection = $this->getMockBuilder(RouteCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMock(RouterInterface::class);
        $this->router->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($this->routeCollection);

        $this->propertyPath = $this->getMockBuilder(PropertyPathInterface::class)
            ->getMock();
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue(self::PROPERTY_PATH_NAME));
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue([self::PROPERTY_PATH_NAME]));

        $this->rfpRouteExists = new RfpRouteExists($this->router);
    }

    public function testGetName()
    {
        $this->assertEquals('rfp_route_exists', $this->rfpRouteExists->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(RfpRouteExists::class, $this->rfpRouteExists->initialize([$this->propertyPath]));
    }

    public function testToArray()
    {
        $result = $this->rfpRouteExists->initialize([$this->propertyPath])->toArray();

        $this->assertEquals('$' . self::PROPERTY_PATH_NAME, $result['@rfp_route_exists']['parameters'][0]);
    }

    public function testCompile()
    {
        $result = $this->rfpRouteExists->compile('$factoryAccessor');

        $this->assertContains('$factoryAccessor->create(\'rfp_route_exists\'', $result);
    }

    public function testSetContextAccessor()
    {
        /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rfpRouteExists->setContextAccessor($contextAccessor);

        $reflection = new \ReflectionProperty(get_class($this->rfpRouteExists), 'contextAccessor');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(get_class($contextAccessor), $reflection->getValue($this->rfpRouteExists));
    }

    /**
     * @dataProvider dataProvider
     * @param Route|null $route
     * @param bool $expected
     */
    public function testEvaluates($route, $expected)
    {
        $this->routeCollection->expects($this->any())
            ->method('get')
            ->willReturn($route);

        /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('oro_rfp_frontend_request_create'));

        $this->rfpRouteExists->initialize([$this->propertyPath])->setContextAccessor($contextAccessor);

        $this->assertEquals($expected, $this->rfpRouteExists->evaluate('oro_rfp_frontend_request_create'));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'route exists' => [
                'route' => new Route('/'),
                'expected' => true,
            ],
            'route not exists' => [
                'route' => null,
                'expected' => false,
            ],
        ];
    }
}
