<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;

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
    protected $appendUsers;

    /**
     * @var AccountUser
     */
    protected $loggedAccountUser;

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(AbstractRole $role, array $appendUsers, array $removeUsers)
    {
        if ($role->getId()) {
            /** @var AccountUserRoleRepository $roleRepository */
            $roleRepository = $this->doctrineHelper->getEntityRepository($role);
            $this->appendUsers = $roleRepository->getAssignedUsers($role);
            $this->loggedAccountUser = $this->securityFacade->getLoggedUser();
        }

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
     */
    protected function addNewRoleToUsers(
        AccountUserRole $role,
        OroEntityManager $manager,
        array $appendUsers,
        array $removeUsers
    ) {
        if ($role->getId() && $role->getId() === $this->newRole->getId()) {
            return;
        }

        $accountRolesToAdd = array_diff($this->appendUsers, $removeUsers);
        $accountRolesToAdd = array_merge($accountRolesToAdd, $appendUsers);
        array_map(function (AccountUser $accountUser) use ($role, $manager) {
            if ($accountUser->getAccount()->getId() == $this->loggedAccountUser->getId()) {
                $accountUser->addRole($this->newRole);
                $manager->persist($accountUser);
            }
        }, $accountRolesToAdd);
        $manager->flush();
    }

    /**
     * @param AccountUserRole|AbstractRole $role
     * @param OroEntityManager             $manager
     */
    protected function removeOriginalRoleFromUsers(AccountUserRole $role, OroEntityManager $manager)
    {
        // TODO: When task BB-1046 will be done, instead off method removeRole add method setRoles([$this->newRole])
        // and remove flush. Also need to remove method addNewRoleToUsers
        if ($role->getId() && $role->getId() === $this->newRole->getId()) {
            return;
        }
            array_map(function (AccountUser $accountUser) use ($role, $manager) {
                if ($accountUser->getAccount()->getId() == $this->loggedAccountUser->getId()) {
                    $accountUser->removeRole($role);
                    $manager->persist($accountUser);
                }
            }, $this->appendUsers);
            $manager->flush();
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * Create form for role manipulation
     *
     * @param AbstractRole $role
     * @return FormInterface
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


}
