<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Factory\CustomerFactory;

class CustomerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerFactory
     */
    protected $factory;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface $groupFactory */
        $groupFactory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        $container->expects($this->once())->method('get')->with($this->isType('string'))->willReturn($groupFactory);

        $this->factory = new CustomerFactory('OroB2B\Bundle\CustomerBundle\Model\CustomerModel', $container);
    }

    protected function tearDown()
    {
        unset($this->factory);
    }

    public function testCreate()
    {
        $customer = new Customer();
        $arguments = [$customer, new \stdClass()];

        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Model\CustomerModel',
            $this->factory->create($arguments)
        );

        $this->assertEquals([$customer], $this->factory->create($arguments)->getEntities());
    }
}
