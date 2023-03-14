<?php

namespace Oro\Bundle\RFPBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Doctrine\SoftDeleteableInterface;
use Oro\Bundle\CustomerBundle\Doctrine\SoftDeleteableTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Request for Quote entity
 *
 * @ORM\Table("oro_rfp_request")
 * @ORM\Entity(repositoryClass="Oro\Bundle\RFPBundle\Entity\Repository\RequestRepository")
 * @Config(
 *      routeName="oro_rfp_request_index",
 *      routeView="oro_rfp_request_view",
 *      routeUpdate="oro_rfp_request_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-file-text"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="quotes"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="customerUser",
 *              "frontend_owner_column_name"="customer_user_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_customer_field_name"="customer",
 *              "frontend_customer_column_name"="customer_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @method AbstractEnumValue getInternalStatus()
 * @method AbstractEnumValue getCustomerStatus()
 */
class Request implements
    CustomerOwnerAwareInterface,
    EmailHolderInterface,
    EmailOwnerInterface,
    SoftDeleteableInterface,
    OrganizationAwareInterface,
    WebsiteAwareInterface,
    ExtendEntityInterface
{
    use SoftDeleteableTrait;
    use DatesAwareTrait;
    use AuditableFrontendCustomerUserAwareTrait;
    use AuditableUserAwareTrait;
    use ExtendEntityTrait;

    const CUSTOMER_STATUS_CODE = 'rfp_customer_status';
    const INTERNAL_STATUS_CODE = 'rfp_internal_status';

    const INTERNAL_STATUS_DELETED = 'deleted';

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
     * @ORM\Column(name="first_name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="company", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $company;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $note;

    /**
     * @var string
     *
     * @ORM\Column(name="cancellation_reason", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $cancellationReason;

    /**
     * @var Collection|RequestProduct[]
     *
     * @ORM\OneToMany(targetEntity="RequestProduct", mappedBy="request", cascade={"ALL"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $requestProducts;

    /**
     * @var string
     *
     * @ORM\Column(name="po_number", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $poNumber;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ship_until", type="date", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shipUntil;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinTable(
     *      name="oro_rfp_assigned_users",
     *      joinColumns={
     *          @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     **/
    protected $assignedUsers;

    /**
     * @var Collection|CustomerUser[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinTable(
     *      name="oro_rfp_assigned_cus_users",
     *      joinColumns={
     *          @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     **/
    protected $assignedCustomerUsers;

    /**
     * @var Collection|RequestAdditionalNote[]
     *
     * @ORM\OneToMany(targetEntity="RequestAdditionalNote", mappedBy="request", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $requestAdditionalNotes;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $website;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;

        $this->requestProducts = new ArrayCollection();
        $this->assignedUsers = new ArrayCollection();
        $this->assignedCustomerUsers = new ArrayCollection();
        $this->requestAdditionalNotes = new ArrayCollection();
    }

    /**
     * Add requestProducts
     *
     * @param RequestProduct $requestProduct
     * @return Request
     */
    public function addRequestProduct(RequestProduct $requestProduct)
    {
        if (!$this->requestProducts->contains($requestProduct)) {
            $this->requestProducts[] = $requestProduct;
            $requestProduct->setRequest($this);
        }

        return $this;
    }

    /**
     * Remove requestProducts
     *
     * @param RequestProduct $requestProduct
     * @return Request
     */
    public function removeRequestProduct(RequestProduct $requestProduct)
    {
        if ($this->requestProducts->contains($requestProduct)) {
            $this->requestProducts->removeElement($requestProduct);
        }

        return $this;
    }

    /**
     * Get requestProducts
     *
     * @return Collection|RequestProduct[]
     */
    public function getRequestProducts()
    {
        return $this->requestProducts;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return Request
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return Request
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Request
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Request
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set company
     *
     * @param string $company
     * @return Request
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return Request
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Request
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s: %s %s', $this->id, $this->firstName, $this->lastName);
    }

    /**
     * Set poNumber
     *
     * @param string $poNumber
     *
     * @return Request
     */
    public function setPoNumber($poNumber)
    {
        $this->poNumber = $poNumber;

        return $this;
    }

    /**
     * Get poNumber
     *
     * @return string
     */
    public function getPoNumber()
    {
        return $this->poNumber;
    }

    /**
     * Set shipUntil
     *
     * @param \DateTime|null $shipUntil
     *
     * @return Request
     */
    public function setShipUntil(\DateTime $shipUntil = null)
    {
        $this->shipUntil = $shipUntil;

        return $this;
    }

    /**
     * Get shipUntil
     */
    public function getShipUntil(): ?\DateTime
    {
        return $this->shipUntil;
    }

    /**
     * @return Collection|User[]
     */
    public function getAssignedUsers()
    {
        return $this->assignedUsers;
    }

    /**
     * @param User $assignedUser
     * @return $this
     */
    public function addAssignedUser(User $assignedUser)
    {
        if (!$this->assignedUsers->contains($assignedUser)) {
            $this->assignedUsers->add($assignedUser);
        }

        return $this;
    }

    /**
     * @param User $assignedUser
     * @return $this
     */
    public function removeAssignedUser(User $assignedUser)
    {
        if ($this->assignedUsers->contains($assignedUser)) {
            $this->assignedUsers->removeElement($assignedUser);
        }

        return $this;
    }

    /**
     * @return Collection|CustomerUser[]
     */
    public function getAssignedCustomerUsers()
    {
        return $this->assignedCustomerUsers;
    }

    /**
     * @param CustomerUser $assignedCustomerUser
     * @return $this
     */
    public function addAssignedCustomerUser(CustomerUser $assignedCustomerUser)
    {
        if (!$this->assignedCustomerUsers->contains($assignedCustomerUser)) {
            $this->assignedCustomerUsers->add($assignedCustomerUser);
        }

        return $this;
    }

    /**
     * @param CustomerUser $assignedCustomerUser
     * @return $this
     */
    public function removeAssignedCustomerUser(CustomerUser $assignedCustomerUser)
    {
        if ($this->assignedCustomerUsers->contains($assignedCustomerUser)) {
            $this->assignedCustomerUsers->removeElement($assignedCustomerUser);
        }

        return $this;
    }

    /**
     * @return Collection|RequestAdditionalNote[]
     */
    public function getRequestAdditionalNotes()
    {
        return $this->requestAdditionalNotes;
    }

    /**
     * @param RequestAdditionalNote $requestAdditionalNote
     * @return $this
     */
    public function addRequestAdditionalNote(RequestAdditionalNote $requestAdditionalNote)
    {
        if (!$this->requestAdditionalNotes->contains($requestAdditionalNote)) {
            $this->requestAdditionalNotes->add($requestAdditionalNote);
        }

        return $this;
    }

    /**
     * @param RequestAdditionalNote $requestAdditionalNote
     * @return $this
     */
    public function removeRequestAdditionalNote(RequestAdditionalNote $requestAdditionalNote)
    {
        if ($this->requestAdditionalNotes->contains($requestAdditionalNote)) {
            $this->requestAdditionalNotes->removeElement($requestAdditionalNote);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCancellationReason()
    {
        return $this->cancellationReason;
    }

    /**
     * @param string $cancellationReason
     * @return $this
     */
    public function setCancellationReason($cancellationReason)
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getPoNumber();
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailFields()
    {
        return ['email'];
    }
}
