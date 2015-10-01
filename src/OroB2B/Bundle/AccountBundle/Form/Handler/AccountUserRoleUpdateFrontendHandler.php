<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\UserBundle\Entity\AbstractRole;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRoleType;

class AccountUserRoleUpdateFrontendHandler extends AbstractAccountUserRoleHandler
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AccountUser
     */
    protected $loggedAccountUser;

    /**
     * @var AccountUserRole
     */
    protected $predefinedRole;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     *
     * @param AccountUserRole $role
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function onSuccess(AbstractRole $role, array $appendUsers, array $removeUsers)
    {
        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManagerForClass(ClassUtils::getClass($this->getLoggedUser()));

        $connection = $manager->getConnection();
        $connection->setTransactionIsolation(Connection::TRANSACTION_REPEATABLE_READ);
        $connection->beginTransaction();

        try {
            $this->removePredefinedRoleAccountUsers($role);

            parent::onSuccess($role, $appendUsers, $removeUsers);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param AccountUserRole $role
     */
    protected function removePredefinedRoleAccountUsers(AccountUserRole $role)
    {
        if ($this->predefinedRole && $this->predefinedRole !== $role) {
            $this->predefinedRole
                ->getAccountUsers()
                ->map(
                    function (AccountUser $accountUser) use ($role) {
                        if ($accountUser->getAccount()->getId() === $role->getAccount()->getId()) {
                            $this->predefinedRole->getAccountUsers()->removeElement($accountUser);
                        }
                    }
                );
        }
    }

    /**
     * {@inheritdoc}
     * @param AccountUserRole $role
     */
    public function createForm(AbstractRole $role)
    {
        if ($role->isPredefined()) {
            $this->predefinedRole = $role;
            $role = $this->createNewRole($role);
        }

        return parent::createForm($role);
    }

    /**
     * {@inheritdoc}
     */
    protected function createRoleFormInstance(AbstractRole $role, array $privilegeConfig)
    {
        $form = $this->formFactory->create(
            FrontendAccountUserRoleType::NAME,
            $role,
            ['privilege_config' => $privilegeConfig, 'predefined_role' => $this->predefinedRole]
        );

        return $form;
    }

    /**
     * @param AccountUserRole $role
     * @return AccountUserRole
     */
    protected function createNewRole(AccountUserRole $role)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getLoggedUser();

        $newRole = clone $role;

        $newRole
            ->setAccount($accountUser->getAccount())
            ->setOrganization($accountUser->getOrganization());

        return $newRole;
    }

    /**
     * @return AccountUser
     */
    protected function getLoggedUser()
    {
        if (!$this->loggedAccountUser) {
            $token = $this->tokenStorage->getToken();

            if ($token) {
                $this->loggedAccountUser = $token->getUser();
            }
        }

        if (!$this->loggedAccountUser instanceof AccountUser) {
            throw new AccessDeniedException();
        }

        return $this->loggedAccountUser;
    }
}
