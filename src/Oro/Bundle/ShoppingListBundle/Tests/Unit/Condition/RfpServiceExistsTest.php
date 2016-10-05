<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Oro\Bundle\ShoppingListBundle\Condition\RfpServiceExists;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class RfpServiceExistsTest extends \PHPUnit_Framework_TestCase
{
    const PROPERTY_PATH_NAME = 'testPropertyPath';

    /**
     * @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var RfpServiceExists
     */
    protected $rfpServiceExists;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    protected function setUp()
    {
        $this->container = $this->getMock(ContainerInterface::class);

        $this->propertyPath = $this->getMockBuilder(PropertyPathInterface::class)
            ->getMock();
        $this->propertyPath->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue(self::PROPERTY_PATH_NAME));
        $this->propertyPath->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue([self::PROPERTY_PATH_NAME]));

        $this->rfpServiceExists = new RfpServiceExists($this->container);
    }

    public function testGetName()
    {
        $this->assertEquals('rfp_service_exists', $this->rfpServiceExists->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(RfpServiceExists::class, $this->rfpServiceExists->initialize([$this->propertyPath]));
    }

    public function testToArray()
    {
        $result = $this->rfpServiceExists->initialize([$this->propertyPath])->toArray();

        $this->assertEquals('$' . self::PROPERTY_PATH_NAME, $result['@rfp_service_exists']['parameters'][0]);
    }

    public function testCompile()
    {
        $result = $this->rfpServiceExists->compile('$factoryAccessor');

        $this->assertContains('$factoryAccessor->create(\'rfp_service_exists\'', $result);
    }

    public function testSetContextAccessor()
    {
        /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rfpServiceExists->setContextAccessor($contextAccessor);

        $reflection = new \ReflectionProperty(get_class($this->rfpServiceExists), 'contextAccessor');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(get_class($contextAccessor), $reflection->getValue($this->rfpServiceExists));
    }



    /**
     * @dataProvider dataProvider
     * @param bool $hasService
     * @param bool $expected
     */
    public function testEvaluates($hasService, $expected)
    {
        $this->container->expects($this->any())
            ->method('has')
            ->willReturn($hasService);

        /** @var ContextAccessorInterface|\PHPUnit_Framework_MockObject_MockObject $contextAccessor **/
        $contextAccessor = $this->getMockBuilder(ContextAccessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('oro_rfp.form.type.extension.frontend_request_data_storage'));

        $this->rfpServiceExists->initialize([$this->propertyPath])->setContextAccessor($contextAccessor);

        $this->assertEquals(
            $expected,
            $this->rfpServiceExists->evaluate('oro_rfp.form.type.extension.frontend_request_data_storage')
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'route exists' => [
                'hasService' => true,
                'expected' => true,
            ],
            'route not exists' => [
                'hasService' => false,
                'expected' => false,
            ],
        ];
    }
}
