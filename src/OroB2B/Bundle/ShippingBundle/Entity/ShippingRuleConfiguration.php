<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ShippingBundle\Model\ExtendShippingRuleConfiguration;

/**
 * @ORM\Table(name="orob2b_shipping_rule_config")
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_name", type="string")
 * @Config
 */
abstract class ShippingRuleConfiguration extends ExtendShippingRuleConfiguration
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
     * @ORM\Column(name="type", type="string", nullable=false)
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
     * @ORM\Column(name="method", type="string", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     * )
     */
    protected $method;

    /**
     * @var string
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=30
     *          }
     *      }
     * )
     */
    protected $enabled = false;

    /**
     * @var ShippingRule
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\ShippingRule", inversedBy="configurations")
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
     * @return string
     */
    abstract public function __toString();

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
    public function getCurrency()
    {
        return $this->rule->getCurrency();
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
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param string $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
}
