<?php

namespace OroB2B\Bundle\UserAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;
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
 */
class User extends BaseUser
{
    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\UserAdminBundle\Entity\Group")
     * @ORM\JoinTable(name="orob2b_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $groups;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL")
     **/
    protected $customer;

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }
}
