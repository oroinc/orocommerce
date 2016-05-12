<?php

namespace OroB2B\Bundle\InvoiceBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\Ownership\FrontendAccountUserAwareTrait;
use OroB2B\Bundle\InvoiceBundle\Model\ExtendInvoice;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(
 *      name="orob2b_invoice",
 *      indexes={@ORM\Index(name="orob2b_invoice_created_at_index", columns={"created_at"})}
 * )
 * @ORM\Entity
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(name="account",
 *          joinColumns=@ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
 *      )
 * })
 * @Config(
 *      routeName="orob2b_invoice_index",
 *      routeUpdate="orob2b_invoice_update",
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
 * @ORM\EntityListeners({ "OroB2B\Bundle\InvoiceBundle\EventListener\ORM\InvoiceEventListener" })
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Invoice extends ExtendInvoice implements
    AccountOwnerAwareInterface,
    OrganizationAwareInterface,
    CurrencyAwareInterface,
    LineItemsAwareInterface,
    DatesAwareInterface
{
    use DatesAwareTrait;
    use AuditableUserAwareTrait;
    use FrontendAccountUserAwareTrait;

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
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
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
     * @ORM\OneToMany(
     *     targetEntity="OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem",
     *     mappedBy="invoice", cascade={"ALL"}, orphanRemoval=true
     * )
     *
     * @ORM\OrderBy({"sortOrder" = "ASC"})
     */
    protected $lineItems;

    /**
     * @var string
     *
     * @ORM\Column(name="subtotal", type="money", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $subtotal;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->setPaymentDueDate($now);
        $this->setInvoiceDate($now);
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
    public function setInvoiceDate(\DateTime $invoiceDate)
    {
        $this->invoiceDate = $invoiceDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPoNumber()
    {
        return $this->poNumber;
    }

    /**
     * @param mixed $poNumber
     * @return $this
     */
    public function setPoNumber($poNumber)
    {
        $this->poNumber = $poNumber;

        return $this;
    }

    /**
     * @return InvoiceLineItem[]|ArrayCollection
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
    public function setPaymentDueDate(\DateTime $paymentDueDate)
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
     * @return string
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * @param string $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
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
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }
}
