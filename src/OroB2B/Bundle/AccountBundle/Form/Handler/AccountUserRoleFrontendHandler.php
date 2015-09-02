<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;

class AccountUserRoleFrontendHandler extends AccountUserRoleHandler
{
    /**
     * @var
     */
    protected $newRole;

    /** @var  SecurityFacade */
    protected $securityFacade;

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(AbstractRole $role, array $appendUsers, array $removeUsers)
    {
        $this->fixUsersByAccount($role, $appendUsers, $removeUsers);
        AclRoleHandler::onSuccess($this->newRole, $appendUsers, $removeUsers);
    }

    /**
     * @param AccountUserRole|AbstractRole $role
     * @param array                        $appendUsers
     * @param array                        $removeUsers
     */
    protected function fixUsersByAccount(AccountUserRole $role, array &$appendUsers, array &$removeUsers)
    {
        if ($role->getId()) {
            /** @var AccountUserRoleRepository $roleRepository */
            $roleRepository = $this->doctrineHelper->getEntityRepository($role);
            $assignedUsers = $roleRepository->getAssignedUsers($role);
            $account = $this->securityFacade->getLoggedUser();
            $manager = $this->getManager($role);
            array_map(function (AccountUser $accountUser) use ($account, $role, $manager, &$appendUsers) {
                if ($accountUser->getAccount()->getId() == $account->getId()) {
                    $accountUser->setRoles([$this->newRole]);
                    $manager->persist($accountUser);
                }
            }, $assignedUsers);
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


}
