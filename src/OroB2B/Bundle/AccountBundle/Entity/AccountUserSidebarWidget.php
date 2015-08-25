<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

use Oro\Bundle\SidebarBundle\Entity\AbstractWidget;

/**
 * Widget
 *
 * @ORM\Table(
 *      name="orob2b_account_user_sdbar_wdg",
 *      indexes={
 *          @ORM\Index(name="b2b_sdbr_wdgs_usr_place_idx", columns={"account_user_id", "placement"}),
 *          @ORM\Index(name="b2b_sdar_wdgs_pos_idx", columns={"position"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SidebarBundle\Entity\Repository\WidgetRepository")
 */
class AccountUserSidebarWidget extends AbstractWidget
{
    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Exclude
     */
    protected $user;
}
