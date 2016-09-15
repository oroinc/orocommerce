<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ShippingBundle\Model\ExtendShippingRuleMethodConfig;

/**
 * @ORM\Table(name="oro_shipping_rule_mthd_config")
 * @ORM\Entity
 * @Config
 */
class ShippingRuleMethodConfig extends ExtendShippingRuleMethodConfig
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $method;

    /**
     * @var string
     *
     * @ORM\Column(name="options", type="array")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=0
     *          }
     *      }
     * )
     */
    protected $options = [];

    /**
     * @var Collection|ShippingRuleMethodConfig[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig",
     *     mappedBy="methodConfig",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $typeConfigs;

    /**
     * @var ShippingRule
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingRule", inversedBy="methodConfigs")
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
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->typeConfigs = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getMethod();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ShippingRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param ShippingRule $rule
     * @return $this
     */
    public function setRule(ShippingRule $rule)
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options ?: [];
    }

    /**
     * @param string $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param ShippingRuleMethodTypeConfig $lineItem
     * @return bool
     */
    public function hasTypeConfig(ShippingRuleMethodTypeConfig $lineItem)
    {
        return $this->typeConfigs->contains($lineItem);
    }

    /**
     * @param ShippingRuleMethodTypeConfig $typeConfig
     * @return $this
     */
    public function addTypeConfig(ShippingRuleMethodTypeConfig $typeConfig)
    {
        if (!$this->hasTypeConfig($typeConfig)) {
            $this->typeConfigs[] = $typeConfig;
            $typeConfig->setMethodConfig($this);
        }

        return $this;
    }

    /**
     * @param ShippingRuleMethodTypeConfig $typeConfig
     * @return $this
     */
    public function removeTypeConfig(ShippingRuleMethodTypeConfig $typeConfig)
    {
        if ($this->hasTypeConfig($typeConfig)) {
            $this->typeConfigs->removeElement($typeConfig);
        }

        return $this;
    }

    /**
     * @return Collection|ShippingRuleMethodTypeConfig[]
     */
    public function getTypeConfigs()
    {
        return $this->typeConfigs;
    }
}
