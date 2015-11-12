<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

abstract class AbstractPriceListRelation implements WebsiteAwareInterface, PriceListAwareInterface
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $priceList;

    /**
     * @var Website
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

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
     * @return mixed
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
}
