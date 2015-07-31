<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("orob2b_account_adr_adr_type",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_account_adr_id_type_name_idx", columns={
 *              "account_address_id",
 *              "type_name"
 *          })
 *      }
 * )
 * @ORM\Entity
 */
class AccountAddressToAddressType extends AbstractAddressToAddressType
{
    /**
     * @var AccountAddress
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountAddress", inversedBy="addressesToTypes")
     * @ORM\JoinColumn(name="account_address_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $address;
}
