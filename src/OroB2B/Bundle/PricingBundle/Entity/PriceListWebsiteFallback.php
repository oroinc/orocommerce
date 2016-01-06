<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="orob2b_price_list_website_fb")
 * @ORM\Entity()
 */
class PriceListWebsiteFallback extends PriceListFallback
{
    const CURRENT_WEBSITE_ONLY = 0;
    const CONFIG = 1;

    /** @var Website
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $website;

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }
}
