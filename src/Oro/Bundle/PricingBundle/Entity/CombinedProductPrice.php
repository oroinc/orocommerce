<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;

/**
 * Entity to store prices combined from price list chain by merge strategy.
 *
 * @method CombinedPriceList getPriceList()
 */
#[ORM\Entity(repositoryClass: CombinedProductPriceRepository::class)]
#[ORM\Table(name: 'oro_price_product_combined')]
#[ORM\Index(
    columns: ['combined_price_list_id', 'product_id', 'currency', 'unit_code', 'quantity'],
    name: 'oro_combined_price_idx'
)]
#[ORM\Index(columns: ['combined_price_list_id', 'product_id', 'merge_allowed'], name: 'oro_cmb_price_mrg_idx')]
#[ORM\Index(columns: ['product_id', 'currency'], name: 'oro_cmb_price_product_currency_idx')]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-usd']])]
class CombinedProductPrice extends BaseProductPrice
{
    /**
     * @var PriceList|null
     **/
    #[ORM\ManyToOne(targetEntity: CombinedPriceList::class, inversedBy: 'prices')]
    #[ORM\JoinColumn(name: 'combined_price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $priceList;

    #[ORM\Column(name: 'merge_allowed', type: Types::BOOLEAN, nullable: false)]
    protected ?bool $mergeAllowed = true;

    /**
     * @var string
     */
    #[ORM\Column(name: 'origin_price_id', type: Types::GUID, nullable: true)]
    protected $originPriceId;

    /**
     * @return string|null
     */
    public function getOriginPriceId()
    {
        return $this->originPriceId;
    }

    /**
     * @param string $originPriceId
     * @return CombinedProductPrice
     */
    public function setOriginPriceId($originPriceId)
    {
        $this->originPriceId = $originPriceId;

        return $this;
    }

    public function isMergeAllowed(): bool
    {
        return $this->mergeAllowed;
    }

    /**
     * @param bool $mergeAllowed
     * @return CombinedProductPrice
     */
    public function setMergeAllowed($mergeAllowed)
    {
        $this->mergeAllowed = $mergeAllowed;

        return $this;
    }
}
