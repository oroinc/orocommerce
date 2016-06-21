<?php

namespace OroB2B\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_product_image_type")
 */
class ProductImageType
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductImage", inversedBy="types")
     * @ORM\JoinColumn(name="product_image_id", referencedColumnName="id", nullable=false)
     */
    protected $productImage;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param ProductImage $productImage
     * @return $this
     */
    public function setProductImage(ProductImage $productImage)
    {
        $this->productImage = $productImage;

        return $this;
    }
}
