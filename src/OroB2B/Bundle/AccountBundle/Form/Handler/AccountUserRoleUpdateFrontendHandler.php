<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleFrontendType;

class AccountUserRoleUpdateFrontendHandler extends AccountUserRoleUpdateHandler
{
    /**
     * @var AccountUserRole
     */
    protected $newRole;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var array
     */
    protected $appendUsers = [];

    /**
     * @var AccountUser
     */
    protected $loggedAccountUser;

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(AbstractRole $role, array $appendUsers, array $removeUsers)
    {
        // TODO: When task BB-1046 will be done, remove method removeOriginalRoleFromUsers.
        // In method addNewRoleToUsers before addRole add method removeRole($role). Also needs delete flush;

        if ($role->getId()) {
            /** @var AccountUserRoleRepository $roleRepository */
            $roleRepository = $this->doctrineHelper->getEntityRepository($role);
            $this->appendUsers = $roleRepository->getAssignedUsers($role);
        }

        $this->loggedAccountUser = $this->securityFacade->getLoggedUser();

        /** @var OroEntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(ClassUtils::getClass($this->loggedAccountUser));

        $this->removeOriginalRoleFromUsers($role, $manager);
        AclRoleHandler::onSuccess($this->newRole, $appendUsers, $removeUsers);
        $this->addNewRoleToUsers($role, $manager, $appendUsers, $removeUsers);
    }

    /**
     * @param AccountUserRole|AbstractRole $role
     * @param OroEntityManager             $manager
     * @param array                        $appendUsers
     * @param array                        $removeUsers
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    protected function addNewRoleToUsers(
        AccountUserRole $role,
        OroEntityManager $manager,
        array $appendUsers,
        array $removeUsers
    ) {
        if (!$role->getId() || $role->getId() === $this->newRole->getId()) {
            return;
        }

        $manager->getConnection()->beginTransaction();
        try {
            $accountRolesToAdd = array_diff($this->appendUsers, $removeUsers);
            $accountRolesToAdd = array_merge($accountRolesToAdd, $appendUsers);
            array_map(
                function (AccountUser $accountUser) use ($role, $manager) {
                    if ($accountUser->getAccount()->getId() == $this->loggedAccountUser->getAccount()->getId()) {
                        $accountUser->addRole($this->newRole);
                        $manager->persist($accountUser);
                    }
                },
                $accountRolesToAdd
            );
            $manager->flush();
            $manager->getConnection()->commit();
        } catch (\Exception $e) {
            $manager->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param AccountUserRole|AbstractRole $role
     * @param OroEntityManager             $manager
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    protected function removeOriginalRoleFromUsers(AccountUserRole $role, OroEntityManager $manager)
    {
        if (!$role->getId() || $role->getId() === $this->newRole->getId()) {
            return;
        }

        $manager->getConnection()->beginTransaction();
        try {
            array_map(
                function (AccountUser $accountUser) use ($role, $manager) {
                    if ($accountUser->getAccount()->getId() == $this->loggedAccountUser->getAccount()->getId()) {
                        $accountUser->removeRole($role);
                        $manager->persist($accountUser);
                    }
                },
                $this->appendUsers
            );

            $manager->flush();
            $manager->getConnection()->commit();
        } catch (\Exception $e) {
            $manager->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function createForm(AbstractRole $role)
    {
        $this->newRole = $role;

        return parent::createForm($role);
    }

    /**
     * {@inheritdoc}
     */
    protected function processPrivileges(AbstractRole $role)
    {
        parent::processPrivileges($this->newRole);
    }

    /**
     * {@inheritdoc}
     */
    protected function createRoleFormInstance(AbstractRole $role, array $privilegeConfig)
    {
        return $this->formFactory->create(
            AccountUserRoleFrontendType::NAME,
            $role,
            ['privilege_config' => $privilegeConfig]
        );
    }

}
