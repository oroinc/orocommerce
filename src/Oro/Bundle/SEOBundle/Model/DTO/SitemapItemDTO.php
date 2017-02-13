<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

class SitemapItemDTO
{
    /**
     * @var string
     */
    private $loc;

    /**
     * @var string
     */
    private $changefreq;

    /**
     * @var float
     */
    private $priority;

    /**
     * @var \DateTime
     */
    private $lastmod;

    /**
     * @param string $loc
     * @param string $changefreq
     * @param float $priority
     * @param \DateTime $lastmod
     */
    public function __construct($loc, $changefreq = null, $priority = null, \DateTime $lastmod = null)
    {
        $this->loc = $loc;
        $this->changefreq = $changefreq;
        $this->priority = $priority;
        $this->lastmod = $lastmod;
    }

    /**
     * @return string
     */
    public function getLoc()
    {
        return $this->loc;
    }

    /**
     * @return null|string
     */
    public function getChangefreq()
    {
        return $this->changefreq;
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
    public function getLastmod()
    {
        return $this->lastmod ? $this->lastmod->format(\DateTime::W3C) : null;
    }
}
