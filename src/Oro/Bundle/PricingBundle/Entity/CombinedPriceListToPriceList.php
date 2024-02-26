<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;

/**
 * Stores relation between combining price list and source price list.
 */
#[ORM\Entity(repositoryClass: CombinedPriceListToPriceListRepository::class)]
#[ORM\Table(name: 'oro_cmb_pl_to_pl')]
#[ORM\Index(columns: ['combined_price_list_id', 'sort_order'], name: 'cmb_pl_to_pl_cmb_prod_sort_idx')]
class CombinedPriceListToPriceList
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class)]
    #[ORM\JoinColumn(name: 'combined_price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?CombinedPriceList $combinedPriceList = null;

    #[ORM\ManyToOne(targetEntity: PriceList::class)]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?PriceList $priceList = null;

    /**
     * @var int order ASC
     */
    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, nullable: false)]
    protected ?int $sortOrder = null;

    #[ORM\Column(name: 'merge_allowed', type: Types::BOOLEAN, nullable: false)]
    protected ?bool $mergeAllowed = true;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CombinedPriceList
     */
    public function getCombinedPriceList()
    {
        return $this->combinedPriceList;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return CombinedPriceListToPriceList
     */
    public function setCombinedPriceList(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;

        return $this;
    }

    public function isMergeAllowed(): bool
    {
        return $this->mergeAllowed;
    }

    public function setMergeAllowed(bool $mergeAllowed): self
    {
        $this->mergeAllowed = $mergeAllowed;

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
     * @return CombinedPriceListToPriceList
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;

        return $this;
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
     * @return CombinedPriceListToPriceList
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
