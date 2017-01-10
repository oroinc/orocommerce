<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

use Oro\Bundle\SidebarBundle\Entity\AbstractSidebarState;

/**
 * Sidebar state storage
 *
 * @ORM\Table(
 *    name="oro_customer_user_sdbar_st",
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="oro_cus_sdbar_st_unq_idx", columns={"customer_user_id", "position"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository")
 */
class CustomerUserSidebarState extends AbstractSidebarState
{
    /**
     * @var CustomerUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Exclude
     */
    protected $user;
}
