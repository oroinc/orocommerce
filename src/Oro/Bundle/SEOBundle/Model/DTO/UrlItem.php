<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

class UrlItem
{
    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $changeFrequency;

    /**
     * @var float
     */
    private $priority;

    /**
     * @var \DateTime
     */
    private $lastModification;

    /**
     * @param string $location
     * @param string $changeFrequency
     * @param float $priority
     * @param \DateTime $lastModification
     */
    public function __construct(
        $location,
        $changeFrequency = null,
        $priority = null,
        \DateTime $lastModification = null
    ) {
        $this->location = $location;
        $this->changeFrequency = $changeFrequency;
        $this->priority = $priority;
        $this->lastModification = $lastModification;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return null|string
     */
    public function getChangeFrequency()
    {
        return $this->changeFrequency;
    }

    /**
     * @return null|float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return null|string
     */
    public function getLastModification()
    {
        return $this->lastModification ? $this->lastModification->format(\DateTime::W3C) : null;
    }
}
