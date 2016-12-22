<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_test_content_variant")
 */
class TestContentVariant
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_page_product", referencedColumnName="id", nullable=true)
     */
    private $product_page_product;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Product|null $product_page_product
     */
    public function setProductPageProduct(Product $product_page_product = null)
    {
        $this->product_page_product = $product_page_product;
    }
}
