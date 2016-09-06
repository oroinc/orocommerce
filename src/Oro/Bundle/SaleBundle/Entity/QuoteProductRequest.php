<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;

/**
 * @ORM\Table(name="oro_sale_quote_prod_request")
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
class QuoteProductRequest extends BaseQuoteProductItem
{
    /**
     * @var QuoteProduct
     *
     * @ORM\ManyToOne(targetEntity="QuoteProduct", inversedBy="quoteProductRequests")
     * @ORM\JoinColumn(name="quote_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteProduct;

    /**
     * @var RequestProductItem
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\RFPBundle\Entity\RequestProductItem")
     * @ORM\JoinColumn(name="request_product_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $requestProductItem;

    /**
     * @param RequestProductItem $requestProductItem
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
    public function setPrice($price = null)
    {
        return parent::setPrice($price);
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->value && null !==  $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }
}
