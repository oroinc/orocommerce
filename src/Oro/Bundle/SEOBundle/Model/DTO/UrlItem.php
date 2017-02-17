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
        return $this->lastModification ? $this->lastModification : null;
    }
}
