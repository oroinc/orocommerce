<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

use Oro\Bundle\SidebarBundle\Entity\AbstractWidget;

/**
 * Widget
 *
 * @ORM\Table(
 *      name="oro_customer_user_sdbar_wdg",
 *      indexes={
 *          @ORM\Index(name="oro_cus_sdbr_wdgs_usr_place_idx", columns={"customer_user_id", "placement"}),
 *          @ORM\Index(name="oro_cus_sdar_wdgs_pos_idx", columns={"position"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SidebarBundle\Entity\Repository\WidgetRepository")
 */
class CustomerUserSidebarWidget extends AbstractWidget
{
    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Exclude
     */
    protected $user;
}
