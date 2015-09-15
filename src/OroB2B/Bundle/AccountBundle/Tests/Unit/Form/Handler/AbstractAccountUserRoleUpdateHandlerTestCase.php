<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormFactory;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateHandler;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;

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
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProviderInterface
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

        $this->ownershipConfigProvider
            = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $this->roleRepository =
            $this->getMockBuilder('\OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->doctrineHelper = $this->getMockBuilder('\Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->setConstructorArgs([$this->managerRegistry])
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->roleRepository);
    }

    /**
     * @param AclRoleHandler $handler
     */
    protected function setRequirementsForHandler(AclRoleHandler $handler)
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
     * @param int             $numberOfUsers
     * @param Account         $account
     * @return \OroB2B\Bundle\AccountBundle\Entity\AccountUser[]
     */
    protected function createUsersWithRole(AccountUserRole $role, $numberOfUsers, Account $account = null)
    {
        /** @var AccountUser[] $users */
        $users = [];
        for ($i = 0; $i < $numberOfUsers; $i++) {
            $user = new AccountUser();
            $user->setUsername('user' . $i . $role->getRole());
            $user->setRoles([$role]);
            $user->setAccount($account);
            $users[] = $user;
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
