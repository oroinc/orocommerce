<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomer;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_customer")
 * @Config()
 */
class Customer extends AbstractCustomer
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\Customer", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var ArrayCollection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\Customer", mappedBy="parent")
     */
    protected $children;

    /**
     * @var CustomerGroup
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup", inversedBy="customers")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    protected $group;
}
