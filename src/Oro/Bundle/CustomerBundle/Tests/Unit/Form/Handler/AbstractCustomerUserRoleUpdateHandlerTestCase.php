<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserRoleUpdateHandler;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\CustomerBundle\Form\Handler\AbstractCustomerUserRoleHandler;

abstract class AbstractCustomerUserRoleUpdateHandlerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormFactory
     */
    protected $formFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AclManager
     */
    protected $aclManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AclPrivilegeRepository
     */
    protected $privilegeRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ChainMetadataProvider
     */
    protected $chainMetadataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $ownershipConfigProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CustomerUserRoleRepository
     */
    protected $roleRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclCacheInterface */
    protected $aclCache;

    /**
     * @var CustomerUserRoleUpdateHandler
     */
    protected $handler;

    /**
     * @var array
     */
    protected $privilegeConfig = [
        'entity' => ['types' => ['entity'], 'fix_values' => false, 'show_default' => true],
        'action' => ['types' => ['action'], 'fix_values' => false, 'show_default' => true],
    ];

    /**
     * @var array
     */
    protected $permissionNames = [
        'entity' => ['entity_name'],
        'action' => ['action_name'],
    ];

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->privilegeRepository =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->chainMetadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->ownershipConfigProvider =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->roleRepository =
            $this->getMockBuilder('\Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->managerRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->doctrineHelper = $this->getMockBuilder('\Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->setConstructorArgs([$this->managerRegistry])
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->roleRepository);

        $this->aclCache = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\AclCacheInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param AbstractCustomerUserRoleHandler $handler
     */
    protected function setRequirementsForHandler(AbstractCustomerUserRoleHandler $handler)
    {
        $handler->setAclManager($this->aclManager);
        $handler->setAclPrivilegeRepository($this->privilegeRepository);
        $handler->setChainMetadataProvider($this->chainMetadataProvider);
        $handler->setOwnershipConfigProvider($this->ownershipConfigProvider);
        $handler->setManagerRegistry($this->managerRegistry);
        $handler->setDoctrineHelper($this->doctrineHelper);
    }

    /**
     * @param CustomerUserRole $role
     * @param int $numberOfUsers
     * @param Account $account
     * @param int $offset
     * @return \Oro\Bundle\CustomerBundle\Entity\CustomerUser[]
     */
    protected function createUsersWithRole(CustomerUserRole $role, $numberOfUsers, Account $account = null, $offset = 0)
    {
        /** @var CustomerUser[] $users */
        $users = [];
        for ($i = 0; $i < $numberOfUsers; $i++) {
            $userId = $offset + $i + 1;
            $user = new CustomerUser();
            $user->setUsername('user_id_' . $userId . '_role_' . $role->getRole());
            $user->setRoles([$role]);
            $user->setAccount($account);
            $users[$userId] = $user;
        }

        return $users;
    }

    /**
     * @param string   $role
     * @param int|null $id
     * @return CustomerUserRole
     */
    protected function createCustomerUserRole($role, $id = null)
    {
        $entity = new CustomerUserRole($role);
        if ($id) {
            $reflection = new \ReflectionProperty(get_class($entity), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($entity, $id);
        }

        return $entity;
    }
}
