<?php

namespace Oro\Bundle\ConsentBundle\SystemConfig;

use Oro\Bundle\ConsentBundle\Entity\Consent;

/**
 * DTO for storing Consent object with sort order value
 */
class ConsentConfig
{
    /**
     * @var Consent|null
     */
    protected $consent;

    /**
     * @var int|null
     */
    protected $sortOrder;

    /**
     * @param Consent|null $consent
     * @param int|null $sortOrder
     */
    public function __construct(Consent $consent = null, $sortOrder = null)
    {
        $this->consent = $consent;
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return Consent
     */
    public function getConsent()
    {
        return $this->consent;
    }

    /**
     * @param Consent|null $consent
     *
     * @return $this
     */
    public function setConsent(Consent $consent = null)
    {
        $this->consent = $consent;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int|null $sortOrder
     *
     * @return $this
     */
    public function setSortOrder($sortOrder = null)
    {
        $this->sortOrder = (int)$sortOrder;

        return $this;
    }
}
