<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository")
 * @ORM\Table(name="oro_customer_user_role",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_customer_user_role_customer_id_label_idx", columns={
 *              "customer_id",
 *              "label"
 *          })
 *      }
 * )
 * @Config(
 *      routeName="oro_customer_customer_user_role_index",
 *      routeUpdate="oro_customer_customer_user_role_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-briefcase"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "ownership"={
 *              "frontend_owner_type"="FRONTEND_ACCOUNT",
 *              "frontend_owner_field_name"="customer",
 *              "frontend_owner_column_name"="customer_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "activity"={
 *              "show_on_page"="\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE"
 *          }
 *      }
 * )
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerUserRole extends AbstractRole implements OrganizationAwareInterface, \Serializable
{
    const PREFIX_ROLE = 'ROLE_FRONTEND_';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true, nullable=false)
     */
    protected $role;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $customer;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $organization;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *      }
     * )
     */
    protected $label;

    /**
     * @var Website[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinTable(
     *      name="oro_customer_role_to_website",
     *      joinColumns={
     *          @ORM\JoinColumn(name="customer_user_role_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $websites;

    /**
     * @var CustomerUser[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser", mappedBy="roles")
     */
    protected $customerUsers;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="self_managed", options={"default"=false})
     */
    protected $selfManaged = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", name="public", options={"default"=true})
     */
    protected $public = true;

    /**
     * @param string|null $role
     */
    public function __construct($role = null)
    {
        if ($role) {
            $this->setRole($role, false);
        }

        $this->websites = new ArrayCollection();
        $this->customerUsers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return CustomerUserRole
     */
    public function setLabel($label)
    {
        $this->label = (string)$label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return static::PREFIX_ROLE;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function addWebsite(Website $website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function removeWebsite(Website $website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }

    /**
     * @return Collection|Website[]
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer|null $customer
     * @return CustomerUserRole
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPredefined()
    {
        return !$this->getCustomer();
    }

    public function __clone()
    {
        $this->id = null;
        $this->setRole($this->getLabel());
        $this->websites = new ArrayCollection();
        $this->customerUsers = new ArrayCollection();
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return $this
     */
    public function addCustomerUser(CustomerUser $customerUser)
    {
        if (!$this->customerUsers->contains($customerUser)) {
            $this->customerUsers[] = $customerUser;
        }

        return $this;
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return $this
     */
    public function removeCustomerUser(CustomerUser $customerUser)
    {
        $this->customerUsers->removeElement($customerUser);

        return $this;
    }

    /**
     * @return Collection|CustomerUser[]
     */
    public function getCustomerUsers()
    {
        return $this->customerUsers;
    }

    /**
     * @return boolean
     */
    public function isSelfManaged()
    {
        return $this->selfManaged;
    }

    /**
     * @param boolean $selfManaged
     * @return $this
     */
    public function setSelfManaged($selfManaged)
    {
        $this->selfManaged = $selfManaged;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param boolean $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(
            [
                $this->id,
                $this->role,
                $this->label,
                $this->selfManaged,
                $this->public
            ]
        );
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->role,
            $this->label,
            $this->selfManaged,
            $this->public
            ) = unserialize($serialized);

        $this->websites     = new ArrayCollection();
        $this->customerUsers = new ArrayCollection();
    }
}
