<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orob2b_customer_group",
 *      indexes={
 *          @ORM\Index(name="orob2b_customer_group_name_idx", columns={"name"})
 *      }
 * )
 */
class CustomerGroup extends AbstractCustomerGroup
{
    /**
     * @var Collection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer", mappedBy="group")
     **/
    protected $customers;
}
