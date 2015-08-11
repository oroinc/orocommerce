<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\AccountBundle\Model\ExtendAccountUserAddress;

/**
 * @ORM\Table("orob2b_account_user_address")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *       defaultValues={
 *          "entity"={
 *              "icon"="icon-map-marker"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="systemOrganization",
 *              "owner_column_name"="system_org_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="owner",
 *              "frontend_owner_column_name"="owner_id",
 *              "organization_field_name"="systemOrganization",
 *              "organization_column_name"="system_org_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserAddressRepository")
 */
class AccountUserAddress extends ExtendAccountUserAddress
{
    /**
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser",
     *      inversedBy="addresses",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $owner;

    /**
     * @var Collection|AccountUserAddressToAddressType[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUserAddressToAddressType",
     *      mappedBy="address",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     **/
    protected $types;

    /**
     * {@inheritdoc}
     */
    protected function createAddressToAddressTypeEntity()
    {
        return new AccountUserAddressToAddressType();
    }
}
