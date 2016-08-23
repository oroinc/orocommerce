<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ShippingBundle\Model\ExtendShippingRuleMethodTypeConfig;

/**
 * @ORM\Table(name="oro_shipping_rule_mthd_tp_cnfg")
 * @ORM\Entity
 * @Config
 */
class ShippingRuleMethodTypeConfig extends ExtendShippingRuleMethodTypeConfig
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
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $type;

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
     * @var ShippingRuleMethodConfig
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig",
     *     inversedBy="typeConfigs"
     * )
     * @ORM\JoinColumn(name="method_config_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $methodConfig;

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
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
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
     * @return ShippingRuleMethodConfig
     */
    public function getMethodConfig()
    {
        return $this->methodConfig;
    }

    /**
     * @param ShippingRuleMethodConfig $methodConfig
     * @return $this
     */
    public function setMethodConfig(ShippingRuleMethodConfig $methodConfig)
    {
        $this->methodConfig = $methodConfig;
        return $this;
    }
}
