<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ShippingBundle\Model\ExtendShippingRule;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository")
 * @ORM\Table(
 *     name="oro_shipping_rule",
 *     indexes={
 *         @ORM\Index(name="oro_shipping_rule_en_cur_idx", columns={"enabled", "currency"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_shipping_rule_index",
 *      routeView="oro_shipping_rule_view",
 *      routeCreate="oro_shipping_rule_create",
 *      routeUpdate="oro_shipping_rule_update",
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
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
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
     * @ORM\Column(name="enabled", type="boolean", nullable=false, options={"default"=true})
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
    protected $enabled = true;

    /**
     * @var string
     *
     * @ORM\Column(name="priority", type="integer")
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
    protected $priority;

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
     * @var Collection|ShippingRuleDestination[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination",
     *     mappedBy="rule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $destinations;

    /**
     * @var Collection|ShippingRuleMethodConfig[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig",
     *     mappedBy="rule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $methodConfigs;

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
    protected $currency;

    /**
     * @var bool
     *
     * @ORM\Column(name="stop_processing", type="boolean", nullable=false, options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=60
     *          }
     *      }
     *  )
     */
    protected $stopProcessing = false;

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
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

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
     * @param ShippingRuleMethodConfig $lineItem
     * @return bool
     */
    public function hasMethodConfig(ShippingRuleMethodConfig $lineItem)
    {
        return $this->methodConfigs->contains($lineItem);
    }

    /**
     * @param ShippingRuleMethodConfig $configuration
     * @return $this
     */
    public function addMethodConfig(ShippingRuleMethodConfig $configuration)
    {
        if (!$this->hasMethodConfig($configuration)) {
            $this->methodConfigs[] = $configuration;
            $configuration->setRule($this);
        }

        return $this;
    }

    /**
     * @param ShippingRuleMethodConfig $configuration
     * @return $this
     */
    public function removeMethodConfig(ShippingRuleMethodConfig $configuration)
    {
        if ($this->hasMethodConfig($configuration)) {
            $this->methodConfigs->removeElement($configuration);
        }

        return $this;
    }

    /**
     * @return Collection|ShippingRuleMethodConfig[]
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
     * @return Collection|ShippingRuleDestination[]
     */
    public function getDestinations()
    {
        return $this->destinations;
    }

    /**
     * @param ShippingRuleDestination $destination
     *
     * @return $this
     */
    public function addDestination(ShippingRuleDestination $destination)
    {
        if (!$this->destinations->contains($destination)) {
            $this->destinations->add($destination);
            $destination->setRule($this);
        }

        return $this;
    }

    /**
     * @param ShippingRuleDestination $destination
     *
     * @return $this
     */
    public function removeDestination(ShippingRuleDestination $destination)
    {
        if ($this->destinations->contains($destination)) {
            $this->destinations->removeElement($destination);
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isStopProcessing()
    {
        return $this->stopProcessing;
    }

    /**
     * @param boolean $stopProcessing
     * @return $this
     */
    public function setStopProcessing($stopProcessing)
    {
        $this->stopProcessing = $stopProcessing;
        return $this;
    }
}
