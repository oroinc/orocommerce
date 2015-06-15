<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\SaleBundle\Model\ExtendQuote;

/**
 * @ORM\Table(name="orob2b_sale_quote")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="orob2b_sale_quote_index",
 *      routeView="orob2b_sale_quote_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class Quote extends ExtendQuote
{
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
     */
    protected $qid;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
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
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

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
     * @var \DateTime
     *
     * @ORM\Column(name="valid_until", type="datetime", nullable=true)
     */
    protected $validUntil;

    /**
     * @var Collection|QuoteProduct[]
     *
     * @ORM\OneToMany(targetEntity="QuoteProduct", mappedBy="quote", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $quoteProducts;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->quoteProducts = new ArrayCollection();
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Quote
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Quote
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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
     * Set owner
     *
     * @param User $owner
     * @return Quote
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Add quoteProducts
     *
     * @param QuoteProduct $quoteProduct
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
     * Set organization
     *
     * @param OrganizationInterface $organization
     * @return Quote
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
