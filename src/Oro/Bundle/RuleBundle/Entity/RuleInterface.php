<?php

namespace Oro\Bundle\RuleBundle\Entity;

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
