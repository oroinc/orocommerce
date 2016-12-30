<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("oro_customer_adr_adr_type",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_customer_adr_id_type_name_idx", columns={
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
     * @var AccountAddress
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\AccountAddress", inversedBy="types")
     * @ORM\JoinColumn(name="customer_address_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $address;
}
