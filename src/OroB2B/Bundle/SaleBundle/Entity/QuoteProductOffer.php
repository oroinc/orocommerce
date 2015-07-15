<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\SaleBundle\Model\BaseQuoteProductItem;

/**
 * QuoteProductOffer
 *
 * @ORM\Table(name="orob2b_sale_quote_prod_offer")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class QuoteProductOffer extends BaseQuoteProductItem
{
    const PRICE_UNIT = 10;
    const PRICE_BUNDLED = 20;

    /**
     * @var QuoteProduct
     *
     * @ORM\ManyToOne(targetEntity="QuoteProduct", inversedBy="quoteProductOffers")
     * @ORM\JoinColumn(name="quote_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteProduct;

    /**
     * @var int
     *
     * @ORM\Column(name="price_type", type="smallint")
     */
    protected $priceType;

    /**
     * @var bool
     *
     * @ORM\Column(name="allow_increments", type="boolean")
     */
    protected $allowIncrements;

    /**
     * Get priceType Titles array
     *
     * @return array
     */
    public static function getPriceTypeTitles()
    {
        static $priceTypeTitles = null;
        if (null === $priceTypeTitles) {
            $priceTypeTitles = [
                static::PRICE_UNIT => 'orob2b.sale.quoteproductoffer.price_type.unit',
                static::PRICE_BUNDLED => 'orob2b.sale.quoteproductoffer.price_type.bundled',
            ];
        }

        return $priceTypeTitles;
    }

    /**
     * Get priceType Title
     *
     * @return string
     */
    public function getPriceTypeTitle()
    {
        $priceType = $this->getPriceType();
        $titles = static::getPriceTypeTitles();

        return isset($titles[$priceType]) ? $titles[$priceType] : '';
    }

    /**
     * Set priceType
     *
     * @param int $priceType
     * @return QuoteProductOffer
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;

        return $this;
    }

    /**
     * Get priceType
     *
     * @return int
     */
    public function getPriceType()
    {
        return $this->priceType;
    }

    /**
     * Set allowIncrements
     *
     * @param bool $allowIncrements
     * @return QuoteProductOffer
     */
    public function setAllowIncrements($allowIncrements)
    {
        $this->allowIncrements = $allowIncrements;

        return $this;
    }

    /**
     * Get allowIncrements
     *
     * @return int
     */
    public function getAllowIncrements()
    {
        return $this->allowIncrements;
    }
}
