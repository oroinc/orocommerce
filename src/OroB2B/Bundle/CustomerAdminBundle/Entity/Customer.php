<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomer;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="orob2b_customer",
 *      indexes={
 *          @ORM\Index(name="orob2b_customer_name_idx", columns={"name"})
 *      }
 * )
 *
 * @Config(
 *      routeName="orob2b_customer_admin_customer_index",
 *      routeView="orob2b_customer_admin_customer_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-user"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 * @Config()
 */
class Customer extends AbstractCustomer
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\Customer", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;

    /**
     * @var Collection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\Customer", mappedBy="parent")
     */
    protected $children;

    /**
     * @var CustomerGroup
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup", inversedBy="customers")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $group;
}
