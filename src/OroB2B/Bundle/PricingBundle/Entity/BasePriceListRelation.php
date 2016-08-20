<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * @ORM\MappedSuperclass
 */
class BasePriceListRelation implements WebsiteAwareInterface, PriceListAwareInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer")
     */
    protected $priority;

    /**
     * @var PriceList
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $priceList;

    /**
     * @var Website
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

    /**
     * @var boolean
     *
     * @ORM\Column(name="merge_allowed", type="boolean", nullable=false, options={"default": true})
     */
    protected $mergeAllowed = true;

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param PriceList $priceList
     * @return $this
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     * @return $this
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMergeAllowed()
    {
        return $this->mergeAllowed;
    }

    /**
     * @param boolean $mergeAllowed
     * @return $this
     */
    public function setMergeAllowed($mergeAllowed)
    {
        $this->mergeAllowed = $mergeAllowed;

        return $this;
    }
}
