<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class GuestShoppingListManager
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * DefaultGuestShoppingListManager constructor.
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param int|null $userId
     * @return null|object
     */
    public function getDefaultUser($userId)
    {
        $userRepository = $this->doctrineHelper->getEntityRepository(User::class);

        if ($userId) {
            return $userRepository->find($userId);
        }

        return $userRepository->findOneBy([], ['id' => 'ASC']);
    }
}
