<?php

namespace Oro\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\NavigationBundle\Entity\AbstractNavigationHistoryItem;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Frontend Navigation History Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *      name="oro_acc_navigation_history",
 *      indexes={
 *          @ORM\Index(name="oro_acc_nav_history_route_idx", columns={"route"}),
 *          @ORM\Index(name="oro_acc_nav_history_entity_id_idx", columns={"entity_id"}),
 *      }
 * )
 */
class NavigationHistoryItem extends AbstractNavigationHistoryItem
{
    /**
     * @var AbstractUser $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;
}
