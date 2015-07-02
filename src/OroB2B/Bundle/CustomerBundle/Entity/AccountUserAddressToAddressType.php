<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("orob2b_account_adr_to_adr_type")
 * @ORM\Entity
 */
class AccountUserAddressToAddressType extends AbstractAddressToAddressType
{
    /**
     * @var AccountUserAddress
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress",
     *      inversedBy="addressesToTypes"
     * )
     * @ORM\JoinColumn(name="account_user_address_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $address;
}
