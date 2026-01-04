<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
* Entity that represents Product Image Type
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_product_image_type')]
#[ORM\Index(columns: ['type'], name: 'idx_oro_product_image_type_type')]
#[Config]
class ProductImageType
{
    public const TYPE_LISTING = 'listing';
    public const TYPE_MAIN = 'main';
    public const TYPE_ADDITIONAL = 'additional';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductImage::class, inversedBy: 'types')]
    #[ORM\JoinColumn(name: 'product_image_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ProductImage $productImage = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    protected ?string $type = null;

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
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
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

    /**
     * @return ProductImage
     */
    public function getProductImage()
    {
        return $this->productImage;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->type;
    }
}
