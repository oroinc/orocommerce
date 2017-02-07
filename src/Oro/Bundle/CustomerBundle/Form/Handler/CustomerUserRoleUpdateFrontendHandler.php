<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserRoleType;

class CustomerUserRoleUpdateFrontendHandler extends AbstractCustomerUserRoleHandler
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var CustomerUser
     */
    protected $loggedCustomerUser;

    /**
     * @var CustomerUserRole
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
     * @param CustomerUserRole $role
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
            FrontendCustomerUserRoleType::NAME,
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
     * @param CustomerUserRole $role
     * @return CustomerUserRole
     */
    protected function createNewRole(CustomerUserRole $role)
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getLoggedUser();

        $newRole = clone $role;

        $newRole
            ->setCustomer($customerUser->getCustomer())
            ->setOrganization($customerUser->getOrganization());

        return $newRole;
    }

    /**
     * @return CustomerUser
     */
    protected function getLoggedUser()
    {
        if (!$this->loggedCustomerUser) {
            $token = $this->tokenStorage->getToken();

            if ($token) {
                $this->loggedCustomerUser = $token->getUser();
            }
        }

        if (!$this->loggedCustomerUser instanceof CustomerUser) {
            throw new AccessDeniedException();
        }

        return $this->loggedCustomerUser;
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
        if ($entity instanceof CustomerUserRole) {
            $entity->setSelfManaged(true);
        }

        $this->applyCustomerLimits($entity, $appendUsers, $removeUsers);

        parent::onSuccess($entity, $appendUsers, $removeUsers);
    }
}
