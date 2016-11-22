<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

use Oro\Bundle\SidebarBundle\Entity\AbstractSidebarState;

/**
 * Sidebar state storage
 *
 * @ORM\Table(
 *    name="oro_account_user_sdbar_st",
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="oro_acc_sdbar_st_unq_idx", columns={"account_user_id", "position"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository")
 */
class AccountUserSidebarState extends AbstractSidebarState
{
    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Exclude
     */
    protected $user;
}
