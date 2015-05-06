<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

class CustomerGroup extends AbstractCustomerGroup
{
    /**
     * @var Collection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer", mappedBy="group")
     **/
    protected $customers;
}
