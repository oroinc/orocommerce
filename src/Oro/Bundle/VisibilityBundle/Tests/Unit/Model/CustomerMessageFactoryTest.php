<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\CustomerBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\VisibilityBundle\Model\CustomerMessageFactory;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Bridge\Doctrine\RegistryInterface;

class CustomerMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CustomerMessageFactory
     */
    protected $customerMessageFactory;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(RegistryInterface::class)
            ->getMock();
        $this->customerMessageFactory = new CustomerMessageFactory($this->registry);
    }

    public function testCreateMessage()
    {
        $params = ['id' => 1];
        /** @var Customer $customer **/
        $customer = $this->getEntity(Customer::class, $params);

        $message = $this->customerMessageFactory->createMessage($customer);
        $this->assertEquals($params, $message);
    }

    public function testGetEntityFromMessage()
    {
        $params = ['id' => 1];
        $customer = $this->getEntity(Customer::class, $params);
        $repository = $this->getMockBuilder(CustomerRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($customer);
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->getMock();
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Customer::class)
            ->willReturn($manager);

        $this->customerMessageFactory->getEntityFromMessage($params);
    }

    public function testGetEntityFromMessageEmptyException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->customerMessageFactory->getEntityFromMessage([]);
    }

    public function testGetEntityFromMessageRequiredIdException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->customerMessageFactory->getEntityFromMessage(['id' => null]);
    }
}
