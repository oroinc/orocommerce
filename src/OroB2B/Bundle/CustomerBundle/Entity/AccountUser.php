<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use OroB2B\Bundle\CustomerBundle\Entity\Traits\AddressEntityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_account_user")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="organizations",
 *          joinTable=@ORM\JoinTable(
 *              name="orob2b_account_user_org",
 *              joinColumns={
 *                  @ORM\JoinColumn(
 *                      name="account_user_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE"
 *                  )
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="organization_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE"
 *                  )
 *              }
 *          )
 *      )
 * })
 * @Config(
 *      routeName="orob2b_customer_account_user_index",
 *      routeView="orob2b_customer_account_user_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "ownership"={
 *              "frontend_owner_type"="FRONTEND_CUSTOMER",
 *              "frontend_owner_field_name"="customer",
 *              "frontend_owner_column_name"="customer_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUser extends AbstractUser implements FullNameInterface, EmailHolderInterface
{
    use AddressEntityTrait;

    const SECURITY_GROUP = 'commerce';

    /**
     * @var AccountUserRole[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole")
     * @ORM\JoinTable(
     *      name="orob2b_acc_user_access_role",
     *      joinColumns={
     *          @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="account_user_role_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    protected $roles;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer",
     *      inversedBy="users",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="SET NULL")
     **/
    protected $customer;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $confirmed = true;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $email;

    /**
     * Name prefix
     *
     * @var string
     *
     * @ORM\Column(name="name_prefix", type="string", length=255, nullable=true)
     */
    protected $namePrefix;

    /**
     * First name
     *
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     */
    protected $firstName;

    /**
     * Middle name
     *
     * @var string
     *
     * @ORM\Column(name="middle_name", type="string", length=255, nullable=true)
     */
    protected $middleName;

    /**
     * Last name
     *
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     */
    protected $lastName;

    /**
     * Name suffix
     *
     * @var string
     *
     * @ORM\Column(name="name_suffix", type="string", length=255, nullable=true)
     */
    protected $nameSuffix;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     */
    protected $birthday;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress",
     *    mappedBy="owner", cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"primary" = "DESC"})
     */
    protected $addresses;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->password,
                $this->salt,
                $this->username,
                $this->enabled,
                $this->confirmed,
                $this->confirmationToken,
                $this->id
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->password,
            $this->salt,
            $this->username,
            $this->enabled,
            $this->confirmed,
            $this->confirmationToken,
            $this->id
            ) = unserialize($serialized);
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return AccountUser
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function createCustomer()
    {
        if (!$this->customer) {
            $this->customer = new Customer();
            $this->customer->setOrganization($this->organization);
            $this->customer->setName(sprintf('%s %s', $this->firstName, $this->lastName));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountNonLocked()
    {
        return $this->isEnabled() && $this->isConfirmed();
    }

    /**
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->confirmed;
    }

    /**
     * @param bool $confirmed
     *
     * @return AbstractUser
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = (bool)$confirmed;

        return $this;
    }

    /**
     * @param string $username
     * @return AccountUser
     */
    public function setUsername($username)
    {
        parent::setUsername($username);

        $this->email = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return AccountUser
     */
    public function setEmail($email)
    {
        $this->email = $email;
        $this->username = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getNamePrefix()
    {
        return $this->namePrefix;
    }

    /**
     * @param string $namePrefix
     * @return AccountUser
     */
    public function setNamePrefix($namePrefix)
    {
        $this->namePrefix = $namePrefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return AccountUser
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * @param string $middleName
     * @return AccountUser
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return AccountUser
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameSuffix()
    {
        return $this->nameSuffix;
    }

    /**
     * @param string $nameSuffix
     * @return AccountUser
     */
    public function setNameSuffix($nameSuffix)
    {
        $this->nameSuffix = $nameSuffix;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $birthday
     * @return AccountUser
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses()
    {
        return $this->addresses;
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
     * @return AccountUser
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
     * @return AccountUser
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->loginCount = 0;
    }

    /**
     * Invoked before the entity is updated.
     *
     * @ORM\PreUpdate
     *
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $excludedFields = ['lastLogin', 'loginCount'];

        if (array_diff_key($event->getEntityChangeSet(), array_flip($excludedFields))) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }
}
