<?php

namespace Oro\Bundle\RuleBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroRuleBundle_Entity_Rule;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Store rule data in database.
 *
 * @mixin OroRuleBundle_Entity_Rule
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_rule')]
#[ORM\Index(columns: ['created_at'], name: 'idx_oro_rule_created_at')]
#[ORM\Index(columns: ['updated_at'], name: 'idx_oro_rule_updated_at')]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-briefcase'], 'dataaudit' => ['auditable' => true]])]
class Rule implements DatesAwareInterface, RuleInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['identity' => true, 'order' => 10]]
    )]
    private ?string $name = null;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 20]])]
    private ?bool $enabled = true;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 30]])]
    private ?int $sortOrder = null;

    #[ORM\Column(name: 'stop_processing', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 40]])]
    private ?bool $stopProcessing = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 50]])]
    private ?string $expression = null;

    #[ORM\PrePersist]
    public function prePersist()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->setCreatedAt($now);
        $this->setUpdatedAt($now);
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @return int
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    #[\Override]
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    #[\Override]
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return int
     */
    #[\Override]
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    #[\Override]
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isStopProcessing()
    {
        return $this->stopProcessing;
    }

    /**
     * @param bool $stopProcessing
     * @return $this
     */
    #[\Override]
    public function setStopProcessing($stopProcessing)
    {
        $this->stopProcessing = $stopProcessing;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @param string $expression
     * @return $this
     */
    #[\Override]
    public function setExpression($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string) $this->name;
    }
}
