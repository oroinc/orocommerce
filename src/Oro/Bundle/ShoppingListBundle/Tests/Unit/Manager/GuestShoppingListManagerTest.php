<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\UserBundle\Entity\User;

class GuestShoppingListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var GuestShoppingListManager */
    private $guestShoppingListManager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $userRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->guestShoppingListManager = new GuestShoppingListManager($this->doctrineHelper);
    }

    public function testGetDefaultUserWithId()
    {
        $user = new User();

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->assertSame($user, $this->guestShoppingListManager->getDefaultUser(1));
    }

    public function testGetDefaultUserWithNull()
    {
        $user = new User();

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->assertSame($user, $this->guestShoppingListManager->getDefaultUser(null));
    }
}
