<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
* PriceListFallback class
*
*/
#[ORM\MappedSuperclass]
class PriceListFallback implements WebsiteAwareInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'fallback', type: Types::INTEGER)]
    protected ?int $fallback = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Website $website = null;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param integer $fallback
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * @return Website
     */
    #[\Override]
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    #[\Override]
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }
}
