<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * Represents history of payment transactions.
 *
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity(repositoryClass: PaymentTransactionRepository::class)]
#[ORM\Table(name: 'oro_payment_transaction')]
#[ORM\UniqueConstraint(name: 'oro_pay_trans_access_uidx', columns: ['access_identifier', 'access_token'])]
#[Config]
class PaymentTransaction implements DatesAwareInterface, OrganizationAwareInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'entity_identifier', type: Types::INTEGER)]
    protected ?int $entityIdentifier = null;

    #[ORM\Column(name: 'access_identifier', type: Types::STRING)]
    protected ?string $accessIdentifier = null;

    #[ORM\Column(name: 'access_token', type: Types::STRING)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]],
        mode: 'hidden'
    )]
    protected ?string $accessToken = null;

    #[ORM\Column(name: 'payment_method', type: Types::STRING)]
    protected ?string $paymentMethod = null;

    #[ORM\Column(name: 'action', type: Types::STRING)]
    protected ?string $action = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'reference', type: Types::STRING, nullable: true)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]],
        mode: 'hidden'
    )]
    protected $reference;

    #[ORM\Column(name: 'amount', type: Types::STRING)]
    protected ?string $amount = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3)]
    protected ?string $currency = null;

    #[ORM\Column(name: 'active', type: Types::BOOLEAN)]
    protected ?bool $active = false;

    #[ORM\Column(name: 'successful', type: Types::BOOLEAN)]
    protected ?bool $successful = false;

    #[ORM\ManyToOne(targetEntity: PaymentTransaction::class, inversedBy: 'relatedPaymentTransactions')]
    #[ORM\JoinColumn(name: 'source_payment_transaction', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?PaymentTransaction $sourcePaymentTransaction = null;

    /**
     * @var Collection<int, PaymentTransaction>
     */
    #[ORM\OneToMany(mappedBy: 'sourcePaymentTransaction', targetEntity: PaymentTransaction::class)]
    protected ?Collection $relatedPaymentTransactions = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'request', type: 'secure_array', nullable: true)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]],
        mode: 'hidden'
    )]
    protected $request;

    /**
     * @var array
     */
    #[ORM\Column(name: 'response', type: 'secure_array', nullable: true)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]],
        mode: 'hidden'
    )]
    protected $response;

    /**
     * @var array
     */
    #[ORM\Column(name: 'transaction_options', type: 'secure_array', nullable: true)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]],
        mode: 'hidden'
    )]
    protected $transactionOptions;

    #[ORM\ManyToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(name: 'frontend_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CustomerUser $frontendOwner = null;

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

    /**
     * @return array
     */
    public function getRequest()
    {
        if (!$this->request) {
            return [];
        }

        return $this->request;
    }

    /**
     * @param array|null $request
     * @return PaymentTransaction
     */
    public function setRequest(?array $request = null)
    {
        $this->request = $request;

        return $this;
    }

    public function getResponse()
    {
        if (!$this->response) {
            return [];
        }

        return $this->response;
    }

    /**
     * @param array|null $response
     * @return PaymentTransaction
     */
    public function setResponse(?array $response = null)
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
     * @return PaymentTransaction|null
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
        $this->amount = (string)round((float)$amount, 2);

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
     * @param array|null $transactionOptions
     * @return PaymentTransaction
     */
    public function setTransactionOptions(?array $transactionOptions = null)
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
     * @return CustomerUser
     */
    public function getFrontendOwner()
    {
        return $this->frontendOwner;
    }

    /**
     * @param CustomerUser|null $frontendOwner
     * @return PaymentTransaction
     */
    public function setFrontendOwner(?CustomerUser $frontendOwner = null)
    {
        $this->frontendOwner = $frontendOwner;

        return $this;
    }

    /**
     * @return bool
     */
    public function isClone()
    {
        if (!$this->sourcePaymentTransaction) {
            return false;
        }

        if ($this->sourcePaymentTransaction->getAction() !== PaymentMethodInterface::VALIDATE) {
            return false;
        }

        return $this->reference === $this->sourcePaymentTransaction->getReference();
    }
}
