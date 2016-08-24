<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(
 *      name="oro_prod_price_ch_trigger",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_changed_product_price_list_unq", columns={
 *              "product_id",
 *              "price_list_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository")
 */
class ProductPriceChangeTrigger
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $priceList;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $product;

    /**
     * @param PriceList $priceList
     * @param Product $product
     */
    public function __construct(PriceList $priceList, Product $product)
    {
        $this->priceList = $priceList;
        $this->product = $product;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
