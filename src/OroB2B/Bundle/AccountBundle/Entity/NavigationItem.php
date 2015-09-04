<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\NavigationBundle\Entity\AbstractNavigationItem;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Navigation Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\NavigationItemRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="orob2b_acc_navigation_item",
 *      indexes={@ORM\Index(name="oro_b2b_sorted_items_idx", columns={"account_user_id", "position"})})
 */
class NavigationItem extends AbstractNavigationItem
{
    /**
     * @var AbstractUser $user
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=false)
     */
    protected $type;
}
