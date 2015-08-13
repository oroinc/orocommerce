<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Model\ExtendAccount;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\AccountBundle\Entity\Repository\AccountRepository")
 * @ORM\Table(
 *      name="orob2b_account",
 *      indexes={
 *          @ORM\Index(name="orob2b_account_name_idx", columns={"name"})
 *      }
 * )
 *
 * @Config(
 *      routeName="orob2b_account_index",
 *      routeView="orob2b_account_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-user"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "form"={
 *              "form_type"="orob2b_account_select",
 *              "grid_name"="account-accounts-select-grid",
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Account extends ExtendAccount
{
    const INTERNAL_RATING_CODE = 'acc_internal_rating';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;

    /**
     * @var Collection|Account[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account", mappedBy="parent")
     */
    protected $children;

    /**
     * @var Collection|AccountAddress[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountAddress",
     *    mappedBy="frontendOwner", cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"primary" = "DESC"})
     */
    protected $addresses;

    /**
     * @var AccountGroup
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountGroup", inversedBy="accounts")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $group;

    /**
     * @var Collection|AccountUser[]
     *
     * @ORM\OneToMany(
     *      targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser",
     *      mappedBy="account",
     *      cascade={"persist"}
     * )
     **/
    protected $users;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->children = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Account
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set parent
     *
     * @param Account $parent
     * @return Account
     */
    public function setParent(Account $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Account
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add addresses
     *
     * @param AbstractDefaultTypedAddress $address
     * @return Account
     */
    public function addAddress(AbstractDefaultTypedAddress $address)
    {
        /** @var AbstractDefaultTypedAddress $address */
        if (!$this->getAddresses()->contains($address)) {
            $this->getAddresses()->add($address);
            $address->setFrontendOwner($this);
            $address->setSystemOrganization($this->getOrganization());

            if ($this->getOwner()) {
                $address->setOwner($this->getOwner());
            }
        }

        return $this;
    }

    /**
     * Remove addresses
     *
     * @param AbstractDefaultTypedAddress $address
     * @return Account
     */
    public function removeAddress(AbstractDefaultTypedAddress $address)
    {
        if ($this->hasAddress($address)) {
            $this->getAddresses()->removeElement($address);
        }

        return $this;
    }

    /**
     * Gets one address that has specified type name.
     *
     * @param string $typeName
     *
     * @return AbstractDefaultTypedAddress|null
     */
    public function getAddressByTypeName($typeName)
    {
        /** @var AbstractDefaultTypedAddress $address */
        foreach ($this->getAddresses() as $address) {
            if ($address->hasTypeWithName($typeName)) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Gets primary address if it's available.
     *
     * @return AbstractDefaultTypedAddress|null
     */
    public function getPrimaryAddress()
    {
        /** @var AbstractDefaultTypedAddress $address */
        foreach ($this->getAddresses() as $address) {
            if ($address->isPrimary()) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Get addresses
     *
     * @return Collection
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param AbstractDefaultTypedAddress $address
     * @return bool
     */
    protected function hasAddress(AbstractDefaultTypedAddress $address)
    {
        return $this->getAddresses()->contains($address);
    }

    /**
     * Set group
     *
     * @param AccountGroup $group
     * @return Account
     */
    public function setGroup(AccountGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return AccountGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Add child
     *
     * @param Account $child
     * @return Account
     */
    public function addChild(Account $child)
    {
        if (!$this->hasChild($child)) {
            $child->setParent($this);
            $this->children->add($child);
        }

        return $this;
    }

    /**
     * Remove child
     *
     * @param Account $child
     * @return Account
     */
    public function removeChild(Account $child)
    {
        if ($this->hasChild($child)) {
            $child->setParent(null);
            $this->children->removeElement($child);
        }

        return $this;
    }

    /**
     * Get children
     *
     * @return Collection|Account[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Account $child
     * @return bool
     */
    protected function hasChild(Account $child)
    {
        return $this->children->contains($child);
    }

    /**
     * @param AccountUser $accountUser
     * @return Account
     */
    public function addUser(AccountUser $accountUser)
    {
        if (!$this->hasUser($accountUser)) {
            $accountUser->setAccount($this);
            if ($this->getOwner()) {
                $accountUser->setOwner($this->getOwner());
            }

            $this->users->add($accountUser);
        }

        return $this;
    }

    /**
     * @param AccountUser $accountUser
     * @return Account
     */
    public function removeUser(AccountUser $accountUser)
    {
        if ($this->hasUser($accountUser)) {
            $accountUser->setAccount(null);
            $this->users->removeElement($accountUser);
        }

        return $this;
    }

    /**
     * Get children
     *
     * @return Collection|AccountUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     * @param bool $force
     *
     * @return Account
     */
    public function setOwner(User $owner, $force = true)
    {
        $this->owner = $owner;

        if ($force) {
            foreach ($this->users as $accountUser) {
                $accountUser->setOwner($owner);
            }

            foreach ($this->addresses as $accountAddress) {
                $accountAddress->setOwner($owner);
            }
        }

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization|null $organization
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;
    }

    /**
     * @param AccountUser $accountUser
     * @return bool
     */
    protected function hasUser(AccountUser $accountUser)
    {
        return $this->users->contains($accountUser);
    }
}
