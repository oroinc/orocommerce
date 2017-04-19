<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;

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
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CatalogBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_page_category", referencedColumnName="id", nullable=true)
     */
    private $category_page_category;

    /**
     * @var Segment
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SegmentBundle\Entity\Segment")
     * @ORM\JoinColumn(name="product_collection_segment", referencedColumnName="id", nullable=true)
     */
    private $product_collection_segment;

    /**
     * @var TestContentNode
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentNode")
     * @ORM\JoinColumn(name="node", referencedColumnName="id", nullable=true)
     */
    private $node;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProductPageProduct()
    {
        return $this->product_page_product;
    }

    /**
     * @param Product|null $product_page_product
     */
    public function setProductPageProduct(Product $product_page_product = null)
    {
        $this->product_page_product = $product_page_product;
    }

    /**
     * @return Category
     */
    public function getCategoryPageCategory()
    {
        return $this->category_page_category;
    }

    /**
     * @param Category|null $category_page_category
     */
    public function setCategoryPageCategory(Category $category_page_category = null)
    {
        $this->category_page_category = $category_page_category;
    }

    /**
     * @return Segment
     */
    public function getProductCollectionSegment()
    {
        return $this->product_collection_segment;
    }

    /**
     * @param Segment|null $product_collection_segment
     */
    public function setProductCollectionSegment(Segment $product_collection_segment = null)
    {
        $this->product_collection_segment = $product_collection_segment;
    }

    /**
     * @param TestContentNode|null $node
     * @return $this
     */
    public function setNode(TestContentNode $node = null)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @return TestContentNode
     */
    public function getNode()
    {
        return $this->node;
    }
}
