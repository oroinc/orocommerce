<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="oro_cmb_pl_to_pl",
 *      indexes={
 *          @ORM\Index(
 *              name="cmb_pl_to_pl_cmb_prod_sort_idx",
 *              columns={"combined_price_list_id", "sort_order"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository")
 */
class CombinedPriceListToPriceList
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $combinedPriceList;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceList;

    /**
     * @var int order ASC
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=false)
     */
    protected $sortOrder;

    /**
     * @var bool
     *
     * @ORM\Column(name="merge_allowed", type="boolean", nullable=false)
     */
    protected $mergeAllowed;

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

    /**
     * @return boolean
     */
    public function isMergeAllowed()
    {
        return $this->mergeAllowed;
    }

    /**
     * @param boolean $mergeAllowed
     * @return CombinedPriceListToPriceList
     */
    public function setMergeAllowed($mergeAllowed)
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
