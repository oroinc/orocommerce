<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
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

    /** @var  RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getCurrentRequest();
    }

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
     * {@inheritdoc}
     */
    protected function getRolePrivileges(AbstractRole $role)
    {
        $sid = $this->aclManager->getSid($this->predefinedRole ?: $role);

        return $this->privilegeRepository->getPrivileges($sid);
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

    /**
     * {@inheritdoc}
     */
    protected function filterPrivileges(ArrayCollection $privileges, array $rootIds)
    {
        $privileges = parent::filterPrivileges($privileges, $rootIds);
        $entityPrefix =  'entity:';

        foreach ($privileges as $privilege) {
            $oid = $privilege->getIdentity()->getId();
            if (strpos($oid, $entityPrefix) === 0) {
                $className = substr($oid, strlen($entityPrefix));

                if ($className === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                    continue;
                }

                $metadata = $this->chainMetadataProvider->getMetadata($className);
                if (!$metadata->hasOwner()) {
                    $privileges->removeElement($privilege);
                }
            }
        }

        return $privileges;
    }

    /**
     * "Success" form handler
     *
     * @param AbstractRole $entity
     * @param User[] $appendUsers
     * @param User[] $removeUsers
     */
    protected function onSuccess(AbstractRole $entity, array $appendUsers, array $removeUsers)
    {
        if ($entity instanceof AccountUserRole) {
            $entity->setSelfManaged(true);
        }

        parent::onSuccess($entity, $appendUsers, $removeUsers);
    }
}
