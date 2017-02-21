<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class UrlItem extends SitemapItem implements UrlItemInterface
{
    /**
     * @var string
     */
    private $changeFrequency;

    /**
     * @var float
     */
    private $priority;

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
        parent::__construct($location, $lastModification);

        $this->changeFrequency = $changeFrequency;
        $this->priority = $priority;
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
}
