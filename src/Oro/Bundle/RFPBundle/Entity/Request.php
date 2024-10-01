<?php

namespace Oro\Bundle\RFPBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroRFPBundle_Entity_Request;
use Oro\Bundle\CustomerBundle\Doctrine\SoftDeleteableInterface;
use Oro\Bundle\CustomerBundle\Doctrine\SoftDeleteableTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\RFPBundle\Entity\Repository\RequestRepository;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Request for Quote entity
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @method EnumOptionInterface getInternalStatus()
 * @method EnumOptionInterface getCustomerStatus()
 * @mixin OroRFPBundle_Entity_Request
 */
#[ORM\Entity(repositoryClass: RequestRepository::class)]
#[ORM\Table('oro_rfp_request')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_rfp_request_index',
    routeView: 'oro_rfp_request_view',
    routeUpdate: 'oro_rfp_request_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-file-text'],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'quotes'],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'frontend_owner_type' => 'FRONTEND_USER',
            'frontend_owner_field_name' => 'customerUser',
            'frontend_owner_column_name' => 'customer_user_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id',
            'frontend_customer_field_name' => 'customer',
            'frontend_customer_column_name' => 'customer_id'
        ],
        'dataaudit' => ['auditable' => true],
        'grid' => ['default' => 'rfp-requests-grid', 'context' => 'rfp-requests-for-context-grid']
    ]
)]
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

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $lastName = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $email = null;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $phone = null;

    #[ORM\Column(name: 'company', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $company = null;

    #[ORM\Column(name: 'role', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $role = null;

    #[ORM\Column(name: 'note', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $note = null;

    #[ORM\Column(name: 'cancellation_reason', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $cancellationReason = null;

    /**
     * @var Collection<int, RequestProduct>
     */
    #[ORM\OneToMany(mappedBy: 'request', targetEntity: RequestProduct::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $requestProducts = null;

    #[ORM\Column(name: 'po_number', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $poNumber = null;

    #[ORM\Column(name: 'ship_until', type: Types::DATE_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?\DateTimeInterface $shipUntil = null;

    /**
     * @var Collection<int, User>
     **/
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'oro_rfp_assigned_users')]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $assignedUsers = null;

    /**
     * @var Collection<int, CustomerUser>
     **/
    #[ORM\ManyToMany(targetEntity: CustomerUser::class)]
    #[ORM\JoinTable(name: 'oro_rfp_assigned_cus_users')]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'customer_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $assignedCustomerUsers = null;

    /**
     * @var Collection<int, RequestAdditionalNote>
     */
    #[ORM\OneToMany(
        mappedBy: 'request',
        targetEntity: RequestAdditionalNote::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    protected ?Collection $requestAdditionalNotes = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Website $website = null;

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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    #[\Override]
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
    #[\Override]
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website|null $website
     * @return $this
     */
    #[\Override]
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    #[\Override]
    public function getEmailFields()
    {
        return ['email'];
    }
}
