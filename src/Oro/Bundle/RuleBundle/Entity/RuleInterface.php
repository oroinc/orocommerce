<?php

namespace Oro\Bundle\RuleBundle\Entity;

/**
 * Defines the contract for rule entities in the RuleBundle.
 *
 * This interface establishes the core API that all rule implementations must provide. Rules are the fundamental
 * building blocks of the rule engine, allowing developers to define conditional logic that can be evaluated
 * against various contexts. Each rule has a name, enabled status, sort order for execution priority,
 * a stop processing flag to control rule chain execution, and an expression that defines the rule's condition logic.
 * Implementations of this interface can be extended by other bundles to create domain-specific rule types
 * (e.g., pricing rules, promotion rules, etc.).
 */
interface RuleInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled);

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @param int $sortOrder
     *
     * @return $this
     */
    public function setSortOrder($sortOrder);

    /**
     * @return bool
     */
    public function isStopProcessing();

    /**
     * @param bool $stopProcessing
     *
     * @return $this
     */
    public function setStopProcessing($stopProcessing);

    /**
     * @return string
     */
    public function getExpression();

    /**
     * @param string $expression
     *
     * @return $this
     */
    public function setExpression($expression);
}
