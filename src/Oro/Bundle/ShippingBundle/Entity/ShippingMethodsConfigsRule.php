<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Model\ExtendShippingMethodsConfigsRule;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="oro_ship_methods_conf_rule",
 *     indexes={
 *         @ORM\Index(name="idx_oro_shipping_methods_configs_rule_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_oro_shipping_methods_configs_rule_updated_at", columns={"updated_at"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class ShippingMethodsConfigsRule extends ExtendShippingMethodsConfigsRule implements RuleOwnerInterface
{
    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    private $id;

    /**
     * @var Rule
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\RuleBundle\Entity\Rule")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
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
    private $rule;

    /**
     * @var Collection|ShippingMethodConfig[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig",
     *     mappedBy="rule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    private $methodConfigs;

    /**
     * @var Collection|ShippingMethodsConfigsRuleDestination[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination",
     *     mappedBy="rule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    private $destinations;

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
     *              "order"=50
     *          }
     *      }
     *  )
     */
    private $currency;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->destinations = new ArrayCollection();
        $this->methodConfigs = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
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
     * @return Rule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param Rule $rule
     * @return $this
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @param ShippingMethodConfig $lineItem
     * @return bool
     */
    public function hasMethodConfig(ShippingMethodConfig $lineItem)
    {
        return $this->methodConfigs->contains($lineItem);
    }

    /**
     * @param ShippingMethodConfig $configuration
     * @return $this
     */
    public function addMethodConfig(ShippingMethodConfig $configuration)
    {
        if (!$this->hasMethodConfig($configuration)) {
            $this->methodConfigs[] = $configuration;
            $configuration->setRule($this);
        }

        return $this;
    }

    /**
     * @param ShippingMethodConfig $configuration
     * @return $this
     */
    public function removeMethodConfig(ShippingMethodConfig $configuration)
    {
        if ($this->hasMethodConfig($configuration)) {
            $this->methodConfigs->removeElement($configuration);
        }

        return $this;
    }

    /**
     * @return Collection|ShippingMethodConfig[]
     */
    public function getMethodConfigs()
    {
        return $this->methodConfigs;
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
     * @return Collection|ShippingMethodsConfigsRuleDestination[]
     */
    public function getDestinations()
    {
        return $this->destinations;
    }

    /**
     * @param ShippingMethodsConfigsRuleDestination $destination
     *
     * @return $this
     */
    public function addDestination(ShippingMethodsConfigsRuleDestination $destination)
    {
        if (!$this->destinations->contains($destination)) {
            $this->destinations->add($destination);
            $destination->setRule($this);
        }

        return $this;
    }

    /**
     * @param ShippingMethodsConfigsRuleDestination $destination
     *
     * @return $this
     */
    public function removeDestination(ShippingMethodsConfigsRuleDestination $destination)
    {
        if ($this->destinations->contains($destination)) {
            $this->destinations->removeElement($destination);
        }

        return $this;
    }
}
