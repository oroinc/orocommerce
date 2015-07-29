<?php

namespace OroB2B\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\OrderBundle\Model\ExtendOrder;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

/**
 * @ORM\Table(name="orob2b_order",indexes={@ORM\Index(name="orob2b_order_created_at_index", columns={"created_at"})})
 * @ORM\Entity
 * @Config(
 *      routeName="orob2b_order_index",
 *      routeView="orob2b_order_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
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
class Order extends ExtendOrder implements OrganizationAwareInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=255, unique=true, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $identifier;

    /**
     * @var \DateTime $createdAt
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
     * @var \DateTime $updatedAt
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
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $accountUser;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer"),
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="SET NULL")
     **/
    protected $account;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var Quote
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\SaleBundle\Entity\Quote")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $quote;

    /**
     * @var Collection|OrderProduct[]
     *
     * @ORM\OneToMany(targetEntity="OrderProduct", mappedBy="order", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $orderProducts;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->orderProducts = new ArrayCollection();
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return Order
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
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
     *
     * @return Order
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
     *
     * @return Order
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owningUser
     *
     * @return Order
     */
    public function setOwner(User $owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * @return AccountUser
     */
    public function getAccountUser()
    {
        return $this->accountUser;
    }

    /**
     * @param AccountUser $accountUser
     * @return Order
     */
    public function setAccountUser(AccountUser $accountUser = null)
    {
        $this->accountUser = $accountUser;

        return $this;
    }

    /**
     * @return Customer
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Customer $account
     * @return Order
     */
    public function setAccount(Customer $account = null)
    {
        $this->account = $account;

        return $this;
    }
    
    /**
     * @param OrganizationInterface $organization
     *
     * @return Order
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }
    
    /**
     * Add orderProducts
     *
     * @param OrderProduct $orderProduct
     * @return Order
     */
    public function addOrderProduct(OrderProduct $orderProduct)
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts[] = $orderProduct;
            $orderProduct->setOrder($this);
        }

        return $this;
    }

    /**
     * Remove orderProducts
     *
     * @param OrderProduct $orderProduct
     * @return Order
     */
    public function removeOrderProduct(OrderProduct $orderProduct)
    {
        if ($this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->removeElement($orderProduct);
        }

        return $this;
    }

    /**
     * Get orderProducts
     *
     * @return Collection|OrderProduct[]
     */
    public function getOrderProducts()
    {
        return $this->orderProducts;
    }
    
    /**
     * Set quote
     *
     * @param Quote $quote
     * @return Order
     */
    public function setQuote(Quote $quote = null)
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * Get quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }
}
