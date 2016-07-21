<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ShippingBundle\Model\ExtendShippingRule;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_shipping_rule")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="orob2b_shipping_rule_index",
 *      routeView="orob2b_shipping_rule_view",
 *      routeCreate="orob2b_shipping_rule_create",
 *      routeUpdate="orob2b_shipping_rule_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
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
class ShippingRule extends ExtendShippingRule
{
    const STATUS_DISABLED = 'disabled';
    const STATUS_ENABLED = 'enabled';

    /**
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
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true,
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="string", length=16, nullable=false)
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
    protected $status = self::STATUS_DISABLED;

    /**
     * @var string
     *
     * @ORM\Column(name="sort_order", type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=30
     *          }
     *      }
     *  )
     */
    protected $sortOrder;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=40
     *          }
     *      }
     *  )
     */
    protected $conditions;

    /**
     * @var Collection|ShippingDestination[]
     *
     * @ORM\OneToMany(targetEntity="ShippingDestination", mappedBy="shippingRule", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $shippingDestinations;

    /**
     * @var Collection|ShippingRuleConfiguration[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration", mappedBy="rule")
     */
    protected $configurations;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->shippingDestinations = new ArrayCollection();
        $this->configurations = new ArrayCollection();
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [self::STATUS_ENABLED, self::STATUS_DISABLED];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return string
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param string $conditions
     * @return $this
     */
    public function setConditions($conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @param ShippingRuleConfiguration $lineItem
     * @return bool
     */
    public function hasConfiguration(ShippingRuleConfiguration $lineItem)
    {
        return $this->configurations->contains($lineItem);
    }

    /**
     * @param ShippingRuleConfiguration $configuration
     * @return $this
     */
    public function addConfiguration(ShippingRuleConfiguration $configuration)
    {
        if (!$this->hasConfiguration($configuration)) {
            $this->configurations[] = $configuration;
            $configuration->setRule($this);
        }

        return $this;
    }

    /**
     * @param ShippingRuleConfiguration $configuration
     * @return $this
     */
    public function removeConfiguration(ShippingRuleConfiguration $configuration)
    {
        if ($this->hasConfiguration($configuration)) {
            $this->configurations->removeElement($configuration);
        }

        return $this;
    }

    /**
     * @param Collection|ShippingRuleConfiguration[] $configurations
     * @return $this
     */
    public function setConfigurations(Collection $configurations)
    {
        foreach ($configurations as $configuration) {
            $configuration->setRule($this);
        }
        $this->configurations = $configurations;

        return $this;
    }

    /**
     * @return Collection|ShippingRuleConfiguration[]
     */
    public function getConfigurations()
    {
        return $this->configurations;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @return Collection|ShippingDestination[]
     */
    public function getShippingDestinations()
    {
        return $this->shippingDestinations;
    }

    /**
     * @param ShippingDestination $shippingDestination
     *
     * @return $this
     */
    public function addShippingDestination(ShippingDestination $shippingDestination)
    {
        if (!$this->shippingDestinations->contains($shippingDestination)) {
            $this->shippingDestinations->add($shippingDestination);
        }

        return $this;
    }

    /**
     * @param ShippingDestination $shippingDestination
     *
     * @return $this
     */
    public function removeShippingDestination(ShippingDestination $shippingDestination)
    {
        if ($this->shippingDestinations->contains($shippingDestination)) {
            $this->shippingDestinations->removeElement($shippingDestination);
        }

        return $this;
    }
}
