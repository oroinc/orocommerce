<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Ownership\AuditableFrontendAccountUserAwareTrait;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\SaleBundle\Model\ExtendQuote;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="orob2b_sale_quote")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\SaleBundle\Entity\Repository\QuoteRepository")
 * @ORM\EntityListeners({"OroB2B\Bundle\SaleBundle\Entity\Listener\QuoteListener"})
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="orob2b_sale_quote_index",
 *      routeView="orob2b_sale_quote_view",
 *      routeUpdate="orob2b_sale_quote_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="accountUser",
 *              "frontend_owner_column_name"="account_user_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Quote extends ExtendQuote implements
    AccountOwnerAwareInterface,
    EmailHolderInterface,
    OrganizationAwareInterface
{
    use AuditableUserAwareTrait;
    use AuditableFrontendAccountUserAwareTrait;
    use DatesAwareTrait;
    
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $qid;

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
     * @var Request
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\RFPBundle\Entity\Request")
     * @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $request;

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
     * @var \DateTime
     *
     * @ORM\Column(name="valid_until", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $validUntil;

    /**
     * @var Collection|QuoteProduct[]
     *
     * @ORM\OneToMany(targetEntity="QuoteProduct", mappedBy="quote", cascade={"ALL"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $quoteProducts;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $locked = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $expired = false;

    /**
     * @var QuoteAddress
     *
     * @ORM\OneToOne(targetEntity="QuoteAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="shipping_address_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shippingAddress;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinTable(
     *      name="oro_quote_assigned_users",
     *      joinColumns={
     *          @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     **/
    protected $assignedUsers;

    /**
     * @var Collection|AccountUser[]
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinTable(
     *      name="oro_quote_assigned_acc_users",
     *      joinColumns={
     *          @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     **/
    protected $assignedAccountUsers;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_estimate_amount", type="money", nullable=true)
     */
    protected $shippingEstimateAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_estimate_currency", type="string", nullable=true, length=3)
     */
    protected $shippingEstimateCurrency;

    /**
     * @var Price
     */
    protected $shippingEstimate;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->quoteProducts = new ArrayCollection();
        $this->assignedUsers = new ArrayCollection();
        $this->assignedAccountUsers = new ArrayCollection();
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
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
     * Set qid
     *
     * @param string $qid
     * @return Quote
     */
    public function setQid($qid)
    {
        $this->qid = $qid;

        return $this;
    }

    /**
     * Get qid
     *
     * @return string
     */
    public function getQid()
    {
        return $this->qid;
    }

    /**
     * Set validUntil
     *
     * @param \DateTime $validUntil
     * @return Quote
     */
    public function setValidUntil(\DateTime $validUntil = null)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * Get validUntil
     *
     * @return \DateTime
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * Add quoteProducts
     *
     * @param QuoteProduct $quoteProduct
     *
     * @return Quote
     */
    public function addQuoteProduct(QuoteProduct $quoteProduct)
    {
        if (!$this->quoteProducts->contains($quoteProduct)) {
            $this->quoteProducts[] = $quoteProduct;
            $quoteProduct->setQuote($this);
        }

        return $this;
    }

    /**
     * Remove quoteProducts
     *
     * @param QuoteProduct $quoteProduct
     *
     * @return Quote
     */
    public function removeQuoteProduct(QuoteProduct $quoteProduct)
    {
        if ($this->quoteProducts->contains($quoteProduct)) {
            $this->quoteProducts->removeElement($quoteProduct);
        }

        return $this;
    }

    /**
     * Get quoteProducts
     *
     * @return Collection|QuoteProduct[]
     */
    public function getQuoteProducts()
    {
        return $this->quoteProducts;
    }

    /**
     * Set request
     *
     * @param Request $request
     *
     * @return Quote
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     *
     * @return Quote
     */
    public function setLocked($locked)
    {
        $this->locked = (bool)$locked;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->expired;
    }

    /**
     * @param bool $expired
     *
     * @return Quote
     */
    public function setExpired($expired)
    {
        $this->expired = (bool)$expired;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        if (null !== $this->getAccountUser()) {
            return (string)$this->getAccountUser()->getEmail();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasOfferVariants()
    {
        foreach ($this->quoteProducts as $quoteProduct) {
            if ($quoteProduct->hasOfferVariants()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set poNumber
     *
     * @param string $poNumber
     *
     * @return Quote
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
     * @param \DateTime $shipUntil
     *
     * @return Quote
     */
    public function setShipUntil(\DateTime $shipUntil = null)
    {
        $this->shipUntil = $shipUntil;

        return $this;
    }

    /**
     * Get shipUntil
     *
     * @return \DateTime
     */
    public function getShipUntil()
    {
        return $this->shipUntil;
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

    /**
     * @return QuoteAddress|null
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param QuoteAddress|null $shippingAddress
     *
     * @return Quote
     */
    public function setShippingAddress(QuoteAddress $shippingAddress = null)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
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
     * @return Collection|AccountUser[]
     */
    public function getAssignedAccountUsers()
    {
        return $this->assignedAccountUsers;
    }

    /**
     * @param AccountUser $assignedAccountUser
     * @return $this
     */
    public function addAssignedAccountUser(AccountUser $assignedAccountUser)
    {
        if (!$this->assignedAccountUsers->contains($assignedAccountUser)) {
            $this->assignedAccountUsers->add($assignedAccountUser);
        }

        return $this;
    }

    /**
     * @param AccountUser $assignedAccountUser
     * @return $this
     */
    public function removeAssignedAccountUser(AccountUser $assignedAccountUser)
    {
        if ($this->assignedAccountUsers->contains($assignedAccountUser)) {
            $this->assignedAccountUsers->removeElement($assignedAccountUser);
        }

        return $this;
    }

    /**
     * Get shipping estimate
     *
     * @return Price|null
     */
    public function getShippingEstimate()
    {
        return $this->shippingEstimate;
    }

    /**
     * Set shipping estimate
     *
     * @param Price $shippingEstimate
     * @return $this
     */
    public function setShippingEstimate($shippingEstimate = null)
    {
        $this->shippingEstimate = $shippingEstimate;

        $this->updateShippingEstimate();

        return $this;
    }

    /**
     * Check if quote is available for acceptance.
     *
     * @return bool
     */
    public function isAcceptable()
    {
        return !$this->isExpired()
            && (!$this->getValidUntil() || $this->getValidUntil() >= new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->shippingEstimateAmount && null !==  $this->shippingEstimateCurrency) {
            $this->shippingEstimate = Price::create($this->shippingEstimateAmount, $this->shippingEstimateCurrency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateShippingEstimate()
    {
        $this->shippingEstimateAmount = $this->shippingEstimate ? $this->shippingEstimate->getValue() : null;
        $this->shippingEstimateCurrency = $this->shippingEstimate ? $this->shippingEstimate->getCurrency() : null;
    }
}
