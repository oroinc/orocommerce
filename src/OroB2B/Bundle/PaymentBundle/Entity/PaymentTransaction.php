<?php

namespace OroB2B\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

/**
 * @ORM\Table(name="orob2b_payment_transaction")
 * @ORM\Entity
 * @Config(
 *       mode="hidden",
 *       defaultValues={
 *          "ownership"={
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="frontendOwner",
 *              "frontend_owner_column_name"="frontend_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          }
 *      }
 * )
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PaymentTransaction implements DatesAwareInterface, OrganizationAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="entity_class", type="string")
     */
    protected $entityClass;

    /**
     * @var int
     * @ORM\Column(name="entity_identifier", type="integer")
     */
    protected $entityIdentifier;

    /**
     * @var string
     * @ORM\Column(name="access_identifier", type="string")
     */
    protected $accessIdentifier;

    /**
     * @var string
     * @ORM\Column(name="access_token", type="string")
     */
    protected $accessToken;

    /**
     * @var string
     * @ORM\Column(name="payment_method", type="string")
     */
    protected $paymentMethod;

    /**
     * @var string
     * @ORM\Column(name="action", type="string")
     */
    protected $action;

    /**
     * @var string
     * @ORM\Column(name="reference", type="string", nullable=true)
     */
    protected $reference;

    /**
     * @var string
     * @ORM\Column(name="amount", type="string")
     */
    protected $amount;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string", length=3)
     */
    protected $currency;

    /**
     * @var bool
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active = false;

    /**
     * @var bool
     * @ORM\Column(name="successful", type="boolean")
     */
    protected $successful = false;

    /**
     * @var PaymentTransaction
     *
     * @ORM\ManyToOne(
     *     targetEntity="OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction",
     *     inversedBy="relatedPaymentTransactions"
     * )
     * @ORM\JoinColumn(name="source_payment_transaction", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourcePaymentTransaction;

    /**
     * @var Collection|PaymentTransaction[]
     *
     * @ORM\OneToMany(
     *     targetEntity="OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction",
     *     mappedBy="sourcePaymentTransaction"
     * )
     */
    protected $relatedPaymentTransactions;

    /**
     * @var array
     * @ORM\Column(name="request", type="secure_array", nullable=true)
     */
    protected $request;

    /**
     * @var array
     * @ORM\Column(name="response", type="secure_array", nullable=true)
     */
    protected $response;

    /**
     * @var array
     * @ORM\Column(name="transaction_options", type="array", nullable=true)
     */
    protected $transactionOptions;

    /**
     * @param AccountUser
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="frontend_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $frontendOwner;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    public function __construct()
    {
        $this->relatedPaymentTransactions = new ArrayCollection();

        $this->accessIdentifier = UUIDGenerator::v4();
        $this->accessToken = UUIDGenerator::v4();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /** {@inheritdoc} */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return PaymentTransaction
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return PaymentTransaction
     */
    public function setAction($action)
    {
        $this->action = (string)$action;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     * @return PaymentTransaction
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = (string)$entityClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityIdentifier()
    {
        return $this->entityIdentifier;
    }

    /**
     * @param int $entityIdentifier
     * @return PaymentTransaction
     */
    public function setEntityIdentifier($entityIdentifier)
    {
        $this->entityIdentifier = (int)$entityIdentifier;

        return $this;
    }

    /** {@inheritdoc} */
    public function getRequest()
    {
        if (!$this->request) {
            return [];
        }

        return $this->request;
    }

    /**
     * @param array $request
     * @return PaymentTransaction
     */
    public function setRequest(array $request = null)
    {
        $this->request = $request;

        return $this;
    }

    /** {@inheritdoc} */
    public function getResponse()
    {
        if (!$this->response) {
            return [];
        }

        return $this->response;
    }

    /**
     * @param array $response
     * @return PaymentTransaction
     */
    public function setResponse(array $response = null)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return PaymentTransaction
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = (string)$paymentMethod;

        return $this;
    }

    /**
     * @param PaymentTransaction $sourcePaymentTransaction
     * @return PaymentTransaction
     */
    public function setSourcePaymentTransaction(PaymentTransaction $sourcePaymentTransaction)
    {
        $this->sourcePaymentTransaction = $sourcePaymentTransaction;

        return $this;
    }

    /**
     * @return PaymentTransaction
     */
    public function getSourcePaymentTransaction()
    {
        return $this->sourcePaymentTransaction;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return PaymentTransaction
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;

        return $this;
    }

    /**
     * @param string $amount
     * @return PaymentTransaction
     */
    public function setAmount($amount)
    {
        $this->amount = (string)round($amount, 2);

        return $this;
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param PaymentTransaction $relatedPaymentTransaction
     * @return PaymentTransaction
     */
    public function addRelatedPaymentTransaction(PaymentTransaction $relatedPaymentTransaction)
    {
        if (!$this->relatedPaymentTransactions->contains($relatedPaymentTransaction)) {
            $this->relatedPaymentTransactions->add($relatedPaymentTransaction);
        }

        return $this;
    }

    /**
     * @param PaymentTransaction $relatedPaymentTransaction
     * @return PaymentTransaction
     */
    public function removeRelatedPaymentTransaction(PaymentTransaction $relatedPaymentTransaction)
    {
        if ($this->relatedPaymentTransactions->contains($relatedPaymentTransaction)) {
            $this->relatedPaymentTransactions->removeElement($relatedPaymentTransaction);
        }

        return $this;
    }

    /**
     * @return Collection|PaymentTransaction[]
     */
    public function getRelatedPaymentTransactions()
    {
        return $this->relatedPaymentTransactions;
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
        $this->currency = (string)$currency;

        return $this;
    }

    /**
     * @param boolean $successful
     * @return PaymentTransaction
     */
    public function setSuccessful($successful)
    {
        $this->successful = (bool)$successful;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->successful;
    }

    /**
     * @return array
     */
    public function getTransactionOptions()
    {
        if (!$this->transactionOptions) {
            return [];
        }

        return $this->transactionOptions;
    }

    /**
     * @param array $transactionOptions
     * @return PaymentTransaction
     */
    public function setTransactionOptions(array $transactionOptions = null)
    {
        $this->transactionOptions = $transactionOptions;

        return $this;
    }

    /**
     * @param string $accessIdentifier
     * @return PaymentTransaction
     */
    public function setAccessIdentifier($accessIdentifier)
    {
        $this->accessIdentifier = (string)$accessIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessIdentifier()
    {
        return $this->accessIdentifier;
    }

    /**
     * @param string $accessToken
     * @return PaymentTransaction
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = (string)$accessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return AccountUser
     */
    public function getFrontendOwner()
    {
        return $this->frontendOwner;
    }

    /**
     * @param AccountUser $frontendOwner
     * @return PaymentTransaction
     */
    public function setFrontendOwner(AccountUser $frontendOwner = null)
    {
        $this->frontendOwner = $frontendOwner;

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

    /**
     * {@inheritdoc}
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }
}
