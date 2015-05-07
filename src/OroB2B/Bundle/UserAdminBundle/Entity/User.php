<?php

namespace OroB2B\Bundle\UserAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\UserBundle\Entity\AbstractUser as BaseUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_user")
 * @Config(
 *      routeName="orob2b_user_admin_user_index",
 *      routeView="orob2b_user_admin_user_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class User extends BaseUser
{
    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\UserAdminBundle\Entity\Group")
     * @ORM\JoinTable(name="orob2b_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;
}
