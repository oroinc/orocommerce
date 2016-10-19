<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("oro_acc_usr_adr_to_adr_type",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_account_user_adr_id_type_name_idx", columns={
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
     *      targetEntity="Oro\Bundle\CustomerBundle\Entity\AccountUserAddress",
     *      inversedBy="types"
     * )
     * @ORM\JoinColumn(name="account_user_address_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $address;
}
