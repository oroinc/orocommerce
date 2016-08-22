<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerFactory;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceListChangeTriggerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListChangeTriggerFactory
     */
    private $factory;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock(RegistryInterface::class);

        $this->factory = new PriceListChangeTriggerFactory($this->registry);
    }

    public function testCreateFromMessage()
    {
        $body = json_encode([
            'website' => 1,
            'account' => 1,
            'accountGroup' => 1,
            'force' => false,
        ]);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->getMock(MessageInterface::class);
        $message->method('getBody')->willReturn($body);

        $website = new Website();
        $account = new Account();
        $accountGroup = new AccountGroup();

        $accountRepository = $this->getMock(ObjectRepository::class);
        $accountRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($account);
        $this->registry->expects($this->at(0))
            ->method('getRepository')
            ->with(Account::class)
            ->willReturn($accountRepository);

        $accountGroupRepository = $this->getMock(ObjectRepository::class);
        $accountGroupRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($accountGroup);
        $this->registry->expects($this->at(1))
            ->method('getRepository')
            ->with(AccountGroup::class)
            ->willReturn($accountGroupRepository);

        $accountRepository = $this->getMock(ObjectRepository::class);
        $accountRepository->expects($this->once())
            ->method('find')->with(1)->willReturn($website);
        $this->registry->expects($this->at(2))
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($accountRepository);

        $expected = (new PriceListChangeTrigger())
            ->setWebsite($website)
            ->setAccount($account)
            ->setAccountGroup($accountGroup);

        $this->assertEquals($expected, $this->factory->createFromMessage($message));
    }

    public function testCreateFromEmptyMessage()
    {
        $body = json_encode([]);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->getMock(MessageInterface::class);
        $message->method('getBody')->willReturn($body);

        $this->assertEquals(new PriceListChangeTrigger(), $this->factory->createFromMessage($message));
    }
}
