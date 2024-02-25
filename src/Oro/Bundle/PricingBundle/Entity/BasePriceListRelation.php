<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Contains all required business logic that must used by all Price List relation
 */
#[ORM\MappedSuperclass]
class BasePriceListRelation implements WebsiteAwareInterface, PriceListAwareInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER)]
    protected ?int $sortOrder = null;

    #[ORM\ManyToOne(targetEntity: PriceList::class)]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?PriceList $priceList = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Website $website = null;

    #[ORM\Column(name: 'merge_allowed', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    protected ?bool $mergeAllowed = true;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = (int)$sortOrder;

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

    public function isMergeAllowed(): bool
    {
        return $this->mergeAllowed;
    }

    /**
     * @param boolean $mergeAllowed
     * @return $this
     */
    public function setMergeAllowed(bool $mergeAllowed): self
    {
        $this->mergeAllowed = $mergeAllowed;

        return $this;
    }
}
