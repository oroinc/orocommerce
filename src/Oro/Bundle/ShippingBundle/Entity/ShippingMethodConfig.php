<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * Store shipping method config in database.
 *
 * @ORM\Table(name="oro_ship_method_config")
 * @ORM\Entity(repositoryClass="Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository")
 * @Config
 */
class ShippingMethodConfig implements ExtendEntityInterface
{
    use ExtendEntityTrait;

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
     * @var array
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
     * @var Collection|ShippingMethodTypeConfig[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig",
     *     mappedBy="methodConfig",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $typeConfigs;

    /**
     * @var ShippingMethodsConfigsRule
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule",
     *     inversedBy="methodConfigs"
     * )
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $methodConfigsRule;

    public function __construct()
    {
        $this->typeConfigs = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getMethod();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ShippingMethodsConfigsRule
     */
    public function getMethodConfigsRule()
    {
        return $this->methodConfigsRule;
    }

    /**
     * @param ShippingMethodsConfigsRule $rule
     * @return $this
     */
    public function setMethodConfigsRule(ShippingMethodsConfigsRule $rule)
    {
        $this->methodConfigsRule = $rule;
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
     * @return array
     */
    public function getOptions()
    {
        return $this->options ?: [];
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param ShippingMethodTypeConfig $lineItem
     * @return bool
     */
    public function hasTypeConfig(ShippingMethodTypeConfig $lineItem)
    {
        return $this->typeConfigs->contains($lineItem);
    }

    /**
     * @param ShippingMethodTypeConfig $typeConfig
     * @return $this
     */
    public function addTypeConfig(ShippingMethodTypeConfig $typeConfig)
    {
        if (!$this->hasTypeConfig($typeConfig)) {
            $this->typeConfigs[] = $typeConfig;
            $typeConfig->setMethodConfig($this);
        }

        return $this;
    }

    /**
     * @param ShippingMethodTypeConfig $typeConfig
     * @return $this
     */
    public function removeTypeConfig(ShippingMethodTypeConfig $typeConfig)
    {
        if ($this->hasTypeConfig($typeConfig)) {
            $this->typeConfigs->removeElement($typeConfig);
        }

        return $this;
    }

    /**
     * @return Collection|ShippingMethodTypeConfig[]
     */
    public function getTypeConfigs()
    {
        return $this->typeConfigs;
    }

    /**
     * @param ShippingMethodTypeInterface $type
     * @return array
     */
    public function getOptionsByType($type)
    {
        foreach ($this->typeConfigs as $methodTypeConfig) {
            if ($methodTypeConfig->getType() === $type->getIdentifier()) {
                return $methodTypeConfig->getOptions();
            }
        }

        return [];
    }

    /**
     * @param ShippingMethodTypeInterface[] $types
     * @return array
     */
    public function getOptionsByTypes($types)
    {
        $optionsTypesArray = [];
        foreach ($this->typeConfigs as $methodTypeConfig) {
            $optionsTypesArray[$methodTypeConfig->getType()] = $methodTypeConfig->getOptions();
        }

        $typesArray = [];
        foreach ($types as $type) {
            $typesArray[] = $type->getIdentifier();
        }

        return array_intersect_key($optionsTypesArray, array_flip($typesArray));
    }
}
