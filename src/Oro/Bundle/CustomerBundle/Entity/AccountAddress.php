<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\CustomerBundle\Model\ExtendAccountAddress;

/**
 * @ORM\Table("oro_account_address")
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
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="owner_id",
 *              "frontend_owner_type"="FRONTEND_ACCOUNT",
 *              "frontend_owner_field_name"="frontendOwner",
 *              "frontend_owner_column_name"="frontend_owner_id",
 *              "organization_field_name"="systemOrganization",
 *              "organization_column_name"="system_org_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\CustomerBundle\Entity\Repository\AccountAddressRepository")
 */
class AccountAddress extends ExtendAccountAddress implements AddressPhoneAwareInterface
{
    /**
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="addresses", cascade={"persist"})
     * @ORM\JoinColumn(name="frontend_owner_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $frontendOwner;

    /**
     * @var Collection|AccountUserAddressToAddressType[]
     *
     * @ORM\OneToMany(
     *      targetEntity="AccountAddressToAddressType",
     *      mappedBy="address",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     */
    protected $types;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "entity"={
     *          "contact_information"="phone"
     *      }
     *  }
     * )
     */
    protected $phone;

    /**
     * {@inheritdoc}
     */
    protected function createAddressToAddressTypeEntity()
    {
        return new AccountAddressToAddressType();
    }

    /**
     * Set phone number
     *
     * @param string $phone
     *
     * @return AccountAddress
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
