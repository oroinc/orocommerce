<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Model\ExtendShippingMethodsConfigsRule;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository")
 * @ORM\Table(
 *     name="oro_ship_method_configs_rule"
 * )
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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\RuleBundle\Entity\Rule", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false, onDelete="SET NULL")
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
     *     mappedBy="methodConfigsRule",
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
     *     mappedBy="methodConfigsRule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    private $destinations;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=20
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
            $configuration->setMethodConfigsRule($this);
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
            $destination->setMethodConfigsRule($this);
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
