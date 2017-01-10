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
        $account = new Customer();
        $accountGroup = new CustomerGroup();

        $accountRepository = $this->createMock(ObjectRepository::class);
        $accountRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($account);
        $this->registry->expects($this->at(0))
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($accountRepository);

        $accountGroupRepository = $this->createMock(ObjectRepository::class);
        $accountGroupRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($accountGroup);
        $this->registry->expects($this->at(1))
            ->method('getRepository')
            ->with(CustomerGroup::class)
            ->willReturn($accountGroupRepository);

        $accountRepository = $this->createMock(ObjectRepository::class);
        $accountRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($website);
        $this->registry->expects($this->at(2))
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($accountRepository);

        $expected = (new PriceListRelationTrigger())
            ->setWebsite($website)
            ->setAccount($account)
            ->setAccountGroup($accountGroup);

        $data = [
            'website' => 1,
            'account' => 1,
            'accountGroup' => 1,
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
