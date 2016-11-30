<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShoppingListOwnerManager
{
    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * ShoppingListOwnerManager constructor.
     * @param AclHelper $aclHelper
     * @param RegistryInterface $registry
     * @param ConfigProvider $configProvider
     */
    public function __construct(AclHelper $aclHelper, RegistryInterface $registry, ConfigProvider $configProvider)
    {
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
        $this->configProvider = $configProvider;
    }

    /**
     * @param int $ownerId
     * @param ShoppingList $shoppingList
     */
    public function setOwner($ownerId, ShoppingList $shoppingList)
    {
        $user = $this->registry->getRepository(AccountUser::class)->find($ownerId);
        if (null === $user) {
            throw new \InvalidArgumentException(sprintf("User with id=%s not exists", $ownerId));
        }
        if ($user === $shoppingList->getAccountUser()) {
            return;
        }
        if ($this->isUserAssignable($ownerId)) {
            $shoppingList->setAccountUser($user);
            $this->registry->getManagerForClass(ShoppingList::class)->flush();
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param int $id
     * @return boolean
     */
    protected function isUserAssignable($id)
    {
        /** @var EntityRepository $repository */
        $repository = $this->registry->getRepository(AccountUser::class);
        $qb = $repository
            ->createQueryBuilder('user')
            ->where("user.id = :id")
            ->setParameter("id", $id);

        $criteria = new Criteria();
        $config = $this->configProvider->getConfig(ShoppingList::class);
        $ownerFieldName = $config->get('frontend_owner_field_name');
        $organizationFieldName = $config->get('organization_field_name');
        $this->aclHelper->applyAclToCriteria(
            ShoppingList::class,
            $criteria,
            "ASSIGN",
            [$ownerFieldName => 'user.id', $organizationFieldName => 'user.organization']
        );
        $qb->addCriteria($criteria);
        $owner = $qb->getQuery()->getOneOrNullResult();

        return null !== $owner;
    }
}
