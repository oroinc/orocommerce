<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;

/**
* Entity that represents Combined Price List
*
*/
#[ORM\Entity(repositoryClass: CombinedPriceListRepository::class)]
#[ORM\Table(name: 'oro_price_list_combined')]
class CombinedPriceList extends BasePriceList
{
    #[ORM\Column(name: 'is_enabled', type: Types::BOOLEAN)]
    protected ?bool $enabled = false;

    /**
     * @var Collection<int, CombinedProductPrice>
     */
    #[ORM\OneToMany(mappedBy: 'priceList', targetEntity: CombinedProductPrice::class, fetch: 'EXTRA_LAZY')]
    protected ?Collection $prices = null;

    /**
     * @var Collection<int, CombinedPriceListCurrency>
     */
    #[ORM\OneToMany(
        mappedBy: 'priceList',
        targetEntity: CombinedPriceListCurrency::class,
        cascade: ['all'],
        orphanRemoval: true
    )]
    protected ?Collection $currencies = null;

    #[ORM\Column(name: 'is_prices_calculated', type: Types::BOOLEAN)]
    protected ?bool $pricesCalculated = false;

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     * @return CombinedPriceList
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPriceListCurrency()
    {
        return new CombinedPriceListCurrency();
    }

    /**
     * @return boolean
     */
    public function isPricesCalculated()
    {
        return $this->pricesCalculated;
    }

    /**
     * @param boolean $pricesCalculated
     * @return $this
     */
    public function setPricesCalculated($pricesCalculated)
    {
        $this->pricesCalculated = $pricesCalculated;

        return $this;
    }
}
