<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerFactory;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceListRelationTriggerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListRelationTriggerFactory
     */
    private $factory;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = $this->createMock(RegistryInterface::class);

        $this->factory = new PriceListRelationTriggerFactory($this->registry);
    }

    public function testCreateFromArray()
    {
        $website = new Website();
        $customer = new Customer();
        $customerGroup = new CustomerGroup();

        $customerRepository = $this->createMock(ObjectRepository::class);
        $customerRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($customer);
        $this->registry->expects($this->at(0))
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($customerRepository);

        $customerGroupRepository = $this->createMock(ObjectRepository::class);
        $customerGroupRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($customerGroup);
        $this->registry->expects($this->at(1))
            ->method('getRepository')
            ->with(CustomerGroup::class)
            ->willReturn($customerGroupRepository);

        $customerRepository = $this->createMock(ObjectRepository::class);
        $customerRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($website);
        $this->registry->expects($this->at(2))
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($customerRepository);

        $expected = (new PriceListRelationTrigger())
            ->setWebsite($website)
            ->setCustomer($customer)
            ->setCustomerGroup($customerGroup);

        $data = [
            'website' => 1,
            'customer' => 1,
            'customerGroup' => 1,
            'force' => false,
        ];
        $this->assertEquals($expected, $this->factory->createFromArray($data));
    }

    public function testCreateFromEmptyArray()
    {
        $body = json_encode([]);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')->willReturn($body);

        $this->assertEquals(new PriceListRelationTrigger(), $this->factory->createFromArray([]));
    }
}
