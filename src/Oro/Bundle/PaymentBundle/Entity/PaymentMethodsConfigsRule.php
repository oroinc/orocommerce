<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\PaymentBundle\Model\ExtendPaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="oro_payment_mtds_cfgs_rl",
 *     indexes={
 *         @ORM\Index(name="idx_oro_payment_mtds_cfgs_rl_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_oro_payment_mtds_cfgs_rl_updated_at", columns={"updated_at"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config
 */
class PaymentMethodsConfigsRule extends ExtendPaymentMethodsConfigsRule implements RuleOwnerInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var Collection|PaymentMethodConfig[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig",
     *     mappedBy="methodsConfigsRule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $methodConfigs;

    /**
     * @var Rule
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\RuleBundle\Entity\Rule", inversedBy="methodsConfigsRule")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $rule;

    /**
     * @var Collection|PaymentMethodsConfigsRuleDestination[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination",
     *     mappedBy="methodsConfigsRule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $destinations;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     *  )
     */
    protected $currency;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
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
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->methodConfigs = new ArrayCollection();
        $this->destinations = new ArrayCollection();
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
     * {@inheritdoc}
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param Rule $rule
     * @return $this
     */
    public function setMethodConfig(Rule $rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|PaymentMethodConfig[]
     */
    public function getMethodConfigs()
    {
        return $this->methodConfigs;
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     * @return bool
     */
    public function hasMethodConfig(PaymentMethodConfig $methodConfig)
    {
        return $this->methodConfigs->contains($methodConfig);
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     * @return $this
     */
    public function addMethodConfig(PaymentMethodConfig $methodConfig)
    {
        if (!$this->hasMethodConfig($methodConfig)) {
            $this->methodConfigs[] = $methodConfig;
        }

        return $this;
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     * @return $this
     */
    public function removeMethodConfig(PaymentMethodConfig $methodConfig)
    {
        if ($this->hasMethodConfig($methodConfig)) {
            $this->methodConfigs->removeElement($methodConfig);
        }

        return $this;
    }

    /**
     * @return Collection|PaymentMethodsConfigsRuleDestination[]
     */
    public function getDestinations()
    {
        return $this->destinations;
    }

    /**
     * @param PaymentMethodsConfigsRuleDestination $destination
     * @return $this
     */
    public function addDestination(PaymentMethodsConfigsRuleDestination $destination)
    {
        if (!$this->destinations->contains($destination)) {
            $this->destinations->add($destination);
        }

        return $this;
    }

    /**
     * @param PaymentMethodsConfigsRuleDestination $destination
     * @return $this
     */
    public function removeDestination(PaymentMethodsConfigsRuleDestination $destination)
    {
        if ($this->destinations->contains($destination)) {
            $this->destinations->removeElement($destination);
        }

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
    public function setCreatedAt($createdAt)
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
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
