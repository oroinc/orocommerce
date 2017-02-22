<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class UrlItem implements UrlItemInterface
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
     * @param \DateTime $lastModification
     * @param string $changeFrequency
     * @param float $priority
     */
    public function __construct(
        $location,
        \DateTime $lastModification = null,
        $changeFrequency = null,
        $priority = null
    ) {
        $this->location = $location;
        $this->changeFrequency = $changeFrequency;
        $this->priority = $priority;
        $this->lastModification = $lastModification;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangeFrequency()
    {
        return $this->changeFrequency;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModification()
    {
        return $this->lastModification ? $this->lastModification->format(\DateTime::W3C) : null;
    }
}
