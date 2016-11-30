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
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountUserRoleUpdateHandler;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserRoleRepository;
use Oro\Bundle\CustomerBundle\Form\Handler\AbstractAccountUserRoleHandler;

abstract class AbstractAccountUserRoleUpdateHandlerTestCase extends \PHPUnit_Framework_TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountUserRoleRepository
     */
    protected $roleRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclCacheInterface */
    protected $aclCache;

    /**
     * @var AccountUserRoleUpdateHandler
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
            $this->getMockBuilder('\Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserRoleRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

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
     * @param AbstractAccountUserRoleHandler $handler
     */
    protected function setRequirementsForHandler(AbstractAccountUserRoleHandler $handler)
    {
        $handler->setAclManager($this->aclManager);
        $handler->setAclPrivilegeRepository($this->privilegeRepository);
        $handler->setChainMetadataProvider($this->chainMetadataProvider);
        $handler->setOwnershipConfigProvider($this->ownershipConfigProvider);
        $handler->setManagerRegistry($this->managerRegistry);
        $handler->setDoctrineHelper($this->doctrineHelper);
    }

    /**
     * @param AccountUserRole $role
     * @param int $numberOfUsers
     * @param Account $account
     * @param int $offset
     * @return \Oro\Bundle\CustomerBundle\Entity\AccountUser[]
     */
    protected function createUsersWithRole(AccountUserRole $role, $numberOfUsers, Account $account = null, $offset = 0)
    {
        /** @var AccountUser[] $users */
        $users = [];
        for ($i = 0; $i < $numberOfUsers; $i++) {
            $userId = $offset + $i + 1;
            $user = new AccountUser();
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
     * @return AccountUserRole
     */
    protected function createAccountUserRole($role, $id = null)
    {
        $entity = new AccountUserRole($role);
        if ($id) {
            $reflection = new \ReflectionProperty(get_class($entity), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($entity, $id);
        }

        return $entity;
    }
}
