<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* BasePriceListCurrency class
*
*/
#[ORM\MappedSuperclass]
class BasePriceListCurrency
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var BasePriceList
     */
    protected $priceList;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3)]
    protected ?string $currency = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param BasePriceList $priceList
     *
     * @return $this
     */
    public function setPriceList(BasePriceList $priceList)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return BasePriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }
}
