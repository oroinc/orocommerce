<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\EventListener\CategoryVisibilityListener;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryVisibilityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CategoryVisibilityStorage
     */
    protected $categoryVisibilityStorage;

    /**
     * @var CategoryVisibilityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->categoryVisibilityStorage = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CategoryVisibilityListener($this->categoryVisibilityStorage);
    }

    protected function tearDown()
    {
        unset($this->categoryVisibilityStorage, $this->listener);
    }

    public function testDefaults()
    {
        $this->assertAttributeEquals(false, 'invalidateAll', $this->listener);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs
     * @param bool $invalidateAll
     * @param array $data
     * @dataProvider postPersistDataProvider
     */
    public function testPostPersist($lifecycleEventArgs, $invalidateAll, $data = [])
    {
        if (isset($data['changeSetValue'])) {
            $this->prepareChangeSet($lifecycleEventArgs, $data['changeSetValue']);
        }

        $this->listener->postPersist($lifecycleEventArgs);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);

        if (isset($data['accountIds'])) {
            $this->assertAttributeEquals($data['accountIds'], 'invalidateAccountIds', $this->listener);
        }
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs
     * @param bool $invalidateAll
     * @dataProvider postUpdateDataProvider
     */
    public function testPostUpdate($lifecycleEventArgs, $invalidateAll)
    {
        $this->listener->postUpdate($lifecycleEventArgs);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs
     * @param bool $invalidateAll
     * @dataProvider postRemoveDataProvider
     */
    public function testPostRemove($lifecycleEventArgs, $invalidateAll)
    {
        $this->listener->postRemove($lifecycleEventArgs);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);
    }

    /**
     * @dataProvider onPostFlushDataProvider
     * @param LifecycleEventArgs $arg
     * @param bool $invalidateAll
     * @param array $invalidateAccountIds
     */
    public function testOnPostFlush(LifecycleEventArgs $arg, $invalidateAll, array $invalidateAccountIds)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OnFlushEventArgs $onFlushEventArgs */
        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|PostFlushEventArgs $postFlushEventArgs */
        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertAttributeEquals(0, 'flushCounter', $this->listener);

        $this->listener->onFlush($onFlushEventArgs);
        $this->assertAttributeEquals(1, 'flushCounter', $this->listener);

        $this->listener->onFlush($onFlushEventArgs);
        $this->assertAttributeEquals(2, 'flushCounter', $this->listener);

        $this->listener->postPersist($arg);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);
        $this->assertAttributeEquals($invalidateAccountIds, 'invalidateAccountIds', $this->listener);

        $this->listener->postFlush($postFlushEventArgs);
        $this->assertAttributeEquals(1, 'flushCounter', $this->listener);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);
        $this->assertAttributeEquals($invalidateAccountIds, 'invalidateAccountIds', $this->listener);

        if ($invalidateAccountIds) {
            $this->categoryVisibilityStorage->expects($this->once())
                ->method('clearData')
                ->with($invalidateAccountIds);
        } else {
            $this->categoryVisibilityStorage->expects($this->once())
                ->method('clearData')
                ->willReturnCallback(function (array $accountIds = null) {
                    $this->assertNull($accountIds);
                });
        }

        $this->listener->postFlush($postFlushEventArgs);
        $this->assertAttributeEquals(0, 'flushCounter', $this->listener);
        $this->assertAttributeEquals(false, 'invalidateAll', $this->listener);
    }

    /**
     * @return array
     */
    public function onPostFlushDataProvider()
    {
        $account = new Account();
        $this->setAccountId($account, 456);

        $accountVisibility = new AccountCategoryVisibility();
        $accountVisibility->setAccount($account);

        return [
            'invalidateAll' => [
                'arg' => $this->getLifecycleEventArgs(new Category()),
                'invalidateAll' => true,
                'invalidateAccountIds' => []
            ],
            'invalidateAccountIds' => [
                'arg' => $this->getLifecycleEventArgs($accountVisibility),
                'invalidateAll' => false,
                'invalidateAccountIds' => [$account->getId()]
            ]
        ];
    }

    public function testOnClear()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OnClearEventArgs $onClearEventArgs */
        $onClearEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnClearEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener->postPersist($this->getLifecycleEventArgs(new Category()));
        $this->assertAttributeEquals(true, 'invalidateAll', $this->listener);

        $this->listener->onClear($onClearEventArgs);

        $this->assertAttributeEquals(false, 'invalidateAll', $this->listener);
    }

    /**
     * @return array
     */
    public function postRemoveDataProvider()
    {
        return [
            'category' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new Category()),
                'invalidateAll' => true,
            ],
            'other' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new \stdClass()),
                'invalidateAll' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function postUpdateDataProvider()
    {
        $changedParent = new Category();
        $changedNonParent = new Category();

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->exactly(2))
            ->method('getEntityChangeSet')
            ->willReturnMap(
                [
                    [$changedParent, ['parentCategory' => 1]],
                    [$changedNonParent, ['nonParentCategory' => 1]],
                ]
            );
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(2))
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $changedParentLifecycleEventArgs = $this->getLifecycleEventArgs($changedParent);
        $changedParentLifecycleEventArgs->expects($this->exactly(1))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $changedNonParentLifecycleEventArgs = $this->getLifecycleEventArgs($changedNonParent);
        $changedNonParentLifecycleEventArgs->expects($this->exactly(1))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        return [
            'category changed parent' => [
                'lifecycleEventArgs' => $changedParentLifecycleEventArgs,
                'invalidateAll' => true,
            ],
            'category changed non parent' => [
                'lifecycleEventArgs' => $changedNonParentLifecycleEventArgs,
                'invalidateAll' => false,
            ],
            'category visibility' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new CategoryVisibility()),
                'invalidateAll' => true,
            ],
            'other' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new \stdClass()),
                'invalidateAll' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function postPersistDataProvider()
    {
        $account = new Account();
        $this->setAccountId($account, 42);
        
        $anotherAccount = new Account();
        $this->setAccountId($account, 123);

        $accountVisibility = new AccountCategoryVisibility();
        $accountVisibility->setAccount($account);

        $accountGroup = new AccountGroup();
        $accountGroup->addAccount($account);
        $accountGroup->addAccount($anotherAccount);

        $accountGroupVisibility = new AccountGroupCategoryVisibility();
        $accountGroupVisibility->setAccountGroup($accountGroup);

        return [
            'category' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new Category()),
                'invalidateAll' => true,
            ],
            'category visibility' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new CategoryVisibility()),
                'invalidateAll' => true,
            ],
            'account' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs($account),
                'invalidateAll' => false,
                'data' => [
                    'changeSetValue' => ['group' => 123],
                    'accountIds' => [$account->getId()]
                ]
            ],
            'account visibility' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs($accountVisibility),
                'invalidateAll' => false,
                'data' => [
                    'accountIds' => [$account->getId()]
                ]
            ],
            'account group visibility' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs($accountGroupVisibility),
                'invalidateAll' => false,
                'data' => [
                    'accountIds' => [$account->getId(), $anotherAccount->getId()]
                ]
            ],
            'other' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new \stdClass()),
                'invalidateAll' => false,
            ],
        ];
    }

    /**
     * @param LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $arg
     * @param array $value
     */
    protected function prepareChangeSet($arg, array $value)
    {
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn($value);

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $arg->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);
    }

    /**
     * @param Account $account
     * @param int $id
     */
    protected function setAccountId(Account $account, $id)
    {
        $class = new \ReflectionClass($account);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($account, $id);
    }

    /**
     * @param object $entity
     * @return \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs
     */
    protected function getLifecycleEventArgs($entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs */
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        return $lifecycleEventArgs;
    }
}
