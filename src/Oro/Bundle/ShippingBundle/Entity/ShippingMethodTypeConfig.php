<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroShippingBundle_Entity_ShippingMethodTypeConfig;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;

/**
 * Store shipping method type config.
 *
 * @mixin OroShippingBundle_Entity_ShippingMethodTypeConfig
 */
#[ORM\Entity(repositoryClass: ShippingMethodTypeConfigRepository::class)]
#[ORM\Table(name: 'oro_ship_method_type_config')]
#[Config]
class ShippingMethodTypeConfig implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10]])]
    protected ?string $type = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'options', type: Types::ARRAY)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 20]])]
    protected $options = [];

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 30]])]
    protected ?bool $enabled = false;

    #[ORM\ManyToOne(targetEntity: ShippingMethodConfig::class, inversedBy: 'typeConfigs')]
    #[ORM\JoinColumn(name: 'method_config_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?ShippingMethodConfig $methodConfig = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * @return ShippingMethodConfig
     */
    public function getMethodConfig()
    {
        return $this->methodConfig;
    }

    /**
     * @param ShippingMethodConfig $methodConfig
     * @return $this
     */
    public function setMethodConfig(ShippingMethodConfig $methodConfig)
    {
        $this->methodConfig = $methodConfig;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
}
