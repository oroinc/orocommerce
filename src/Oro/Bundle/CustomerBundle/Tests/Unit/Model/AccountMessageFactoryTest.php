<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\AccountBundle\Model\AccountMessageFactory;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Bridge\Doctrine\RegistryInterface;

class AccountMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var AccountMessageFactory
     */
    protected $accountMessageFactory;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(RegistryInterface::class)
            ->getMock();
        $this->accountMessageFactory = new AccountMessageFactory($this->registry);
    }

    public function testCreateMessage()
    {
        $params = ['id' => 1];
        /** @var Account $account **/
        $account = $this->getEntity(Account::class, $params);

        $message = $this->accountMessageFactory->createMessage($account);
        $this->assertEquals($params, $message);
    }

    public function testGetEntityFromMessage()
    {
        $params = ['id' => 1];
        $account = $this->getEntity(Account::class, $params);
        $repository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($account);
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->getMock();
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Account::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Account::class)
            ->willReturn($manager);

        $this->accountMessageFactory->getEntityFromMessage($params);
    }

    public function testGetEntityFromMessageEmptyException()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->accountMessageFactory->getEntityFromMessage([]);
    }

    public function testGetEntityFromMessageRequiredIdException()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->accountMessageFactory->getEntityFromMessage(['id' => null]);
    }
}
