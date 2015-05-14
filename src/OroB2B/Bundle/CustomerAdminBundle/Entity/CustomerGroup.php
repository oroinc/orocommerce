<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomerGroup;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orob2b_customer_group",
 *      indexes={
 *          @ORM\Index(name="orob2b_customer_group_name_idx", columns={"name"})
 *      }
 * )
 *
 * @Config(
 *      routeName="orob2b_customer_admin_group_index",
 *      routeView="orob2b_customer_admin_group_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-group"
 *          },
 *          "form"={
 *              "form_type"="orob2b_customer_admin_customer_group_select",
 *              "grid_name"="customer-groups-grid",
 *          }
 *      }
 * )
 */
class CustomerGroup extends AbstractCustomerGroup
{
    /**
     * @var Collection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\Customer", mappedBy="group")
     **/
    protected $customers;
}
