<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository")
 * @ORM\Table(
 *      name="oro_price_list_to_product",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_price_list_to_product_uidx",
 *              columns={"product_id", "price_list_id"}
 *          )
 *      }
 * )
 * @ORM\EntityListeners({ "Oro\Bundle\PricingBundle\Entity\EntityListener\PriceListProductEntityListener" })
 */
class PriceListToProduct
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
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PricingBundle\Entity\PriceList", inversedBy="assignedProducts")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceList;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_manual", type="boolean")
     */
    protected $manual = true;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return $this
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;

        return $this;
    }
    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isManual()
    {
        return $this->manual;
    }

    /**
     * @param bool $manual
     * @return $this
     */
    public function setManual($manual)
    {
        $this->manual = $manual;

        return $this;
    }
}
