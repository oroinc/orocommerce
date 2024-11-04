<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;

/**
 * Represents a quote product line item request.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_sale_quote_prod_request')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-file-text-o'],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'quotes']
    ]
)]
class QuoteProductRequest extends BaseQuoteProductItem
{
    #[ORM\ManyToOne(targetEntity: QuoteProduct::class, inversedBy: 'quoteProductRequests')]
    #[ORM\JoinColumn(name: 'quote_product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?QuoteProduct $quoteProduct = null;

    #[ORM\ManyToOne(targetEntity: RequestProductItem::class)]
    #[ORM\JoinColumn(name: 'request_product_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?RequestProductItem $requestProductItem = null;

    /**
     * @param RequestProductItem|null $requestProductItem
     * @return QuoteProductRequest
     */
    public function setRequestProductItem(RequestProductItem $requestProductItem = null)
    {
        $this->requestProductItem = $requestProductItem;

        return $this;
    }

    /**
     * @return RequestProductItem
     */
    public function getRequestProductItem()
    {
        return $this->requestProductItem;
    }

    /**
     * @param Price $price
     * @return $this
     */
    #[\Override]
    public function setPrice($price = null)
    {
        return parent::setPrice($price);
    }

    #[ORM\PostLoad]
    #[\Override]
    public function postLoad()
    {
        if (null !== $this->value && null !==  $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }
}
