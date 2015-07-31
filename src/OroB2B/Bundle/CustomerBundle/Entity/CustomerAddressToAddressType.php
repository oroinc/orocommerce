<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("orob2b_customer_adr_adr_type",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_customer_adr_id_type_name_idx", columns={
 *              "customer_address_id",
 *              "type_name"
 *          })
 *      }
 * )
 * @ORM\Entity
 */
class CustomerAddressToAddressType extends AbstractAddressToAddressType
{
    /**
     * @var CustomerAddress
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress", inversedBy="addressesToTypes")
     * @ORM\JoinColumn(name="customer_address_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $address;
}
