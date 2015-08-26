<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("orob2b_acc_usr_adr_to_adr_type",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_account_user_adr_id_type_name_idx", columns={
 *              "account_user_address_id",
 *              "type_name"
 *          })
 *      }
 * )
 * @ORM\Entity
 */
class AccountUserAddressToAddressType extends AbstractAddressToAddressType
{
    /**
     * @var AccountUserAddress
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress",
     *      inversedBy="types"
     * )
     * @ORM\JoinColumn(name="account_user_address_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $address;
}
