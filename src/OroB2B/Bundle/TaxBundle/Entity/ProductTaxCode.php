<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\TaxBundle\Model\TaxCodeInterface;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository")
 * @ORM\Table(name="orob2b_tax_product_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="orob2b_tax_product_tax_code_index",
 *      routeView="orob2b_tax_product_tax_code_view",
 *      routeUpdate="orob2b_tax_product_tax_code_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class ProductTaxCode extends AbstractTaxCode
{
    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinTable(
     *      name="orob2b_tax_prod_tax_code_prod",
     *      joinColumns={
     *          @ORM\JoinColumn(name="product_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     *
     * @var Product[]|Collection
     */
    protected $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /**
     * Add product
     *
     * @param Product $product
     * @return $this
     */
    public function addProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    /**
     * Remove product
     *
     * @param Product $product
     * @return $this
     */
    public function removeProduct(Product $product)
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }

    /**
     * Get products
     *
     * @return Product[]|Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return TaxCodeInterface::TYPE_PRODUCT;
    }
}
