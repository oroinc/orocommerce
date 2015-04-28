<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomerGroup;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_customer_group")
 * @Config()
 */
class CustomerGroup extends AbstractCustomerGroup
{
    /**
     * @var ArrayCollection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerAdminBundle\Entity\Customer", mappedBy="group")
     **/
    protected $customers;
}
