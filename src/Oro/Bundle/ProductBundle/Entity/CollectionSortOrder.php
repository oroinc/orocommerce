<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Model\ExtendCollectionSortOrder;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Represents collection sort orders table
 * @ORM\Table(name="oro_product_collection_sort_order")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class CollectionSortOrder extends ExtendCollectionSortOrder
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var float|null
     *
     * @ORM\Column(name="sort_order", type="float", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=false
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $sortOrder;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected Product $product;

    /**
     * @var Segment
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SegmentBundle\Entity\Segment")
     * @ORM\JoinColumn(name="segment_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected Segment $segment;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return CollectionSortOrder
     */
    public function setProduct(Product $product): CollectionSortOrder
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return Segment
     */
    public function getSegment(): Segment
    {
        return $this->segment;
    }

    /**
     * @param Segment $segment
     * @return CollectionSortOrder
     */
    public function setSegment(Segment $segment): CollectionSortOrder
    {
        $this->segment = $segment;
        return $this;
    }
}
