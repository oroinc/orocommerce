<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\CustomerBundle\Factory\CustomerGroupFactory;

class CustomerGroupFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerGroupFactory
     */
    protected $factory;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface $groupFactory */
        $groupFactory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        $container->expects($this->once())->method('get')->with($this->isType('string'))->willReturn($groupFactory);

        $this->factory = new CustomerGroupFactory(
            'OroB2B\Bundle\CustomerBundle\Model\CustomerGroupModel',
            'OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup',
            $container
        );
    }

    protected function tearDown()
    {
        unset($this->factory);
    }

    public function testCreate()
    {
        $customerGroup = new CustomerGroup();
        $arguments = [$customerGroup, new \stdClass()];

        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Model\CustomerGroupModel',
            $this->factory->create($arguments)
        );

        $this->assertEquals([$customerGroup], $this->factory->create($arguments)->getEntities());
    }
}
