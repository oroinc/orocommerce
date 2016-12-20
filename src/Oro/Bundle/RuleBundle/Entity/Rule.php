<?php

namespace Oro\Bundle\RuleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Model\ExtendRule;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="oro_rule",
 *     indexes={
 *         @ORM\Index(name="idx_oro_rule_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_oro_rule_updated_at", columns={"updated_at"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-briefcase"
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
class Rule extends ExtendRule implements DatesAwareInterface
{
    use DatesAwareTrait;

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
    private $name;

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
    private $enabled = true;

    /**
     * @var int
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
    private $sortOrder;

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
     *              "order"=40
     *          }
     *      }
     *  )
     */
    private $stopProcessing = false;

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
     *              "order"=50
     *          }
     *      }
     *  )
     */
    private $expression;

    /**
     * @var Collection|PaymentMethodsConfigsRule[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule",
     *     mappedBy="rule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $methodsConfigsRule;

    public function __construct()
    {
        parent::__construct();

        $this->methodsConfigsRule = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->setCreatedAt($now);
        $this->setUpdatedAt($now);
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
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
     * @return bool
     */
    public function isStopProcessing()
    {
        return $this->stopProcessing;
    }

    /**
     * @param bool $stopProcessing
     * @return $this
     */
    public function setStopProcessing($stopProcessing)
    {
        $this->stopProcessing = $stopProcessing;

        return $this;
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @param string $expression
     * @return $this
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * @return Collection|PaymentMethodsConfigsRule[]
     */
    public function getMethodsConfigsRule()
    {
        return $this->methodsConfigsRule;
    }

    /**
     * @param PaymentMethodsConfigsRule $methodsConfigsRule
     * @return bool
     */
    public function hasMethodsConfigsRule(PaymentMethodsConfigsRule $methodsConfigsRule)
    {
        return $this->methodsConfigsRule->contains($methodsConfigsRule);
    }

    /**
     * @param PaymentMethodsConfigsRule $methodsConfigsRule
     * @return $this
     */
    public function addMethodsConfigsRule(PaymentMethodsConfigsRule $methodsConfigsRule)
    {
        if (!$this->hasMethodsConfigsRule($methodsConfigsRule)) {
            $this->methodsConfigsRule[] = $methodsConfigsRule;
        }

        return $this;
    }

    /**
     * @param PaymentMethodsConfigsRule $methodsConfigsRule
     * @return $this
     */
    public function removeMethodsConfigsRule(PaymentMethodsConfigsRule $methodsConfigsRule)
    {
        if ($this->hasMethodsConfigsRule($methodsConfigsRule)) {
            $this->methodsConfigsRule->removeElement($methodsConfigsRule);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->name;
    }
}
