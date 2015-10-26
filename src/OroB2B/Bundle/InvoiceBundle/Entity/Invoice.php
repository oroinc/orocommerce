<?php

namespace OroB2B\Bundle\InvoiceBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\InvoiceBundle\Model\ExtendInvoice;

/**
 * @ORM\Table(
 *      name="orob2b_invoice",
 *      indexes={@ORM\Index(name="orob2b_invoice_created_at_index", columns={"created_at"})}
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="accountUser",
 *              "frontend_owner_column_name"="account_user_id",
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Invoice extends ExtendInvoice implements OrganizationAwareInterface
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    //TODO: Use Proper Trait
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_number", type="string", length=255, unique=true, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * ))
     */
    protected $invoiceNumber;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="invoice_date", type="date")
     */
    protected $invoiceDate;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
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
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $organization;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $accountUser;

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
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    protected $currency;

    /**
     * @var \DateTime
     * @ORM\Column(name="payment_due_date", type="date")
     */
    protected $paymentDueDate;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem",
     *      mappedBy="invoice", cascade={"ALL"}, orphanRemoval=true
     * )
     */
    protected $lineItems;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->lineItems = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


    /**
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @param string $invoiceNumber
     * @return $this
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInvoiceDate()
    {
        return $this->invoiceDate;
    }

    /**
     * @param \DateTime $invoiceDate
     * @return $this
     */
    public function setInvoiceDate($invoiceDate)
    {
        $this->invoiceDate = $invoiceDate;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     * @return $this
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return OrganizationInterface|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface|null $organization
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return $this
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return AccountUser|null
     */
    public function getAccountUser()
    {
        return $this->accountUser;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return $this
     */
    public function setAccountUser(AccountUser $accountUser = null)
    {
        $this->accountUser = $accountUser;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPoNumber()
    {
        return $this->poNumber;
    }

    /**
     * @param string|null $poNumber
     * @return $this
     */
    public function setPoNumber($poNumber = null)
    {
        $this->poNumber = $poNumber;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param InvoiceLineItem $lineItem
     * @return bool
     */
    public function hasLineItem(InvoiceLineItem $lineItem)
    {
        return $this->lineItems->contains($lineItem);
    }

    /**
     * @param InvoiceLineItem $lineItem
     * @return $this
     */
    public function addLineItem(InvoiceLineItem $lineItem)
    {
        if (!$this->hasLineItem($lineItem)) {
            $this->lineItems->add($lineItem);
            $lineItem->setInvoice($this);
        }

        return $this;
    }

    /**
     * @param InvoiceLineItem $lineItem
     * @return $this
     */
    public function removeLineItem(InvoiceLineItem $lineItem)
    {
        if ($this->hasLineItem($lineItem)) {
            $this->lineItems->removeElement($lineItem);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPaymentDueDate()
    {
        return $this->paymentDueDate;
    }

    /**
     * @param \DateTime $paymentDueDate
     * @return $this
     */
    public function setPaymentDueDate($paymentDueDate)
    {
        $this->paymentDueDate = $paymentDueDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->invoiceDate = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
