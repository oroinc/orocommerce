<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
* Entity that represents Test Content Variant
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_test_content_variant')]
class TestContentVariant
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_page_product', referencedColumnName: 'id', nullable: true)]
    private ?Product $product_page_product = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_page_category', referencedColumnName: 'id', nullable: true)]
    private ?Category $category_page_category = null;

    #[ORM\ManyToOne(targetEntity: Segment::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(
        name: 'product_collection_segment',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private ?Segment $product_collection_segment = null;

    #[ORM\ManyToOne(targetEntity: TestContentNode::class)]
    #[ORM\JoinColumn(name: 'node', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?TestContentNode $node = null;

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

    public function setProductPageProduct(?Product $product_page_product = null)
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

    public function setCategoryPageCategory(?Category $category_page_category = null)
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

    public function setProductCollectionSegment(?Segment $product_collection_segment = null)
    {
        $this->product_collection_segment = $product_collection_segment;
    }

    /**
     * @param TestContentNode|null $node
     * @return $this
     */
    public function setNode(?TestContentNode $node = null)
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
