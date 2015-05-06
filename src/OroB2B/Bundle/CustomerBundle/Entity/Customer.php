<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

class Customer extends AbstractCustomer
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;

    /**
     * @var Collection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer", mappedBy="parent")
     */
    protected $children;

    /**
     * @var CustomerGroup
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup", inversedBy="customers")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $group;
}
