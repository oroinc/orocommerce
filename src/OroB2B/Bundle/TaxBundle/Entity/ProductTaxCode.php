<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository")
 * @ORM\Table(name="orob2b_tax_product_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="orob2b_tax_product_tax_code_index",
 *      routeView="orob2b_tax_product_tax_code_view"
 * )
 */
class ProductTaxCode extends AbstractTaxCode
{
    /**
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     *
     * @var Product
     */
    protected $product;

    /**
     * Set product
     *
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
