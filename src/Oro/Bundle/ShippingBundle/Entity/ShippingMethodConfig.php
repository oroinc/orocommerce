<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroShippingBundle_Entity_ShippingMethodConfig;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * Store shipping method config in database.
 *
 * @mixin OroShippingBundle_Entity_ShippingMethodConfig
 */
#[ORM\Entity(repositoryClass: ShippingMethodConfigRepository::class)]
#[ORM\Table(name: 'oro_ship_method_config')]
#[Config]
class ShippingMethodConfig implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'method', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10]])]
    protected ?string $method = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'options', type: Types::ARRAY)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 0]])]
    protected $options = [];

    /**
     * @var Collection<int, ShippingMethodTypeConfig>
     */
    #[ORM\OneToMany(
        mappedBy: 'methodConfig',
        targetEntity: ShippingMethodTypeConfig::class,
        cascade: ['ALL'],
        fetch: 'EAGER',
        orphanRemoval: true
    )]
    protected ?Collection $typeConfigs = null;

    #[ORM\ManyToOne(targetEntity: ShippingMethodsConfigsRule::class, inversedBy: 'methodConfigs')]
    #[ORM\JoinColumn(name: 'rule_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?ShippingMethodsConfigsRule $methodConfigsRule = null;

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
