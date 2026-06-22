<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteProductOfferRepository;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;

/**
 * Represents a quote product line item offer.
 */
#[ORM\Entity(repositoryClass: QuoteProductOfferRepository::class)]
#[ORM\Table(name: 'oro_sale_quote_prod_offer')]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: [
    'entity' => ['icon' => 'fa-list-alt'],
    'security' => ['type' => 'ACL', 'group_name' => ''],
    'email' => ['available_in_template' => true],
])]
class QuoteProductOffer extends BaseQuoteProductItem
{
    public const PRICE_TYPE_UNIT       = 10;
    public const PRICE_TYPE_BUNDLED    = 20;

    #[ORM\ManyToOne(targetEntity: QuoteProduct::class, inversedBy: 'quoteProductOffers')]
    #[ORM\JoinColumn(name: 'quote_product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?QuoteProduct $quoteProduct = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'price_type', type: Types::SMALLINT)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected $priceType = self::PRICE_TYPE_UNIT;

    #[ORM\Column(name: 'allow_increments', type: Types::BOOLEAN, options: ['default' => false])]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?bool $allowIncrements = false;

    /**
     * @return array
     */
    public static function getPriceTypes()
    {
        return [
            self::PRICE_TYPE_UNIT       => 'unit',
            self::PRICE_TYPE_BUNDLED    => 'bundled',
        ];
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
        $this->allowIncrements = (bool)$allowIncrements;

        return $this;
    }

    /**
     * Get allowIncrements
     *
     * @return bool
     */
    public function isAllowIncrements()
    {
        return $this->allowIncrements;
    }

    #[\Override]
    public function getProductSku()
    {
        return parent::getProductSku() ?? $this->getQuoteProduct()?->getProductSku();
    }
}
