<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\AccountBundle\Model\ExtendAccountAddress;

/**
 * @ORM\Table("orob2b_account_address")
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
 *          }
 *      }
 * )
 * @ORM\Entity
 */
class AccountAddress extends ExtendAccountAddress
{
    /**
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="addresses", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $owner;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="AccountAddressToAddressType",
     *      mappedBy="address",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     */
    protected $addressesToTypes;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAddressToAddressTypeEntity()
    {
        return new AccountAddressToAddressType();
    }
}
