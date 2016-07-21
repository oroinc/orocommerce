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
 * @ORM\DiscriminatorColumn(name="method", type="string")
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
     * @ORM\Column(type="string", name="type", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=5
     *          }
     *      }
     * )
     */
    protected $type;

    /**
     * @var ShippingRule
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\ShippingRule", inversedBy="configurations")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id")
     */
    protected $rule;

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
}
