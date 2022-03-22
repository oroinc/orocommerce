<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents product kit item label.
 *
 * @ORM\Table(
 *      name="oro_product_prod_kit_item_label",
 *      indexes={
 *          @ORM\Index(name="idx_product_prod_kit_fallback", columns={"fallback"}),
 *          @ORM\Index(name="idx_product_prod_kit_string", columns={"string"})
 *      }
 * )
 * @ORM\Entity
 * @Config()
 */
class ProductKitItemLabel extends AbstractLocalizedFallbackValue
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="string", type="string", length=255, nullable=true)
     */
    protected $string;

    /**
     * @var ProductKitItem|null
     *
     * @ORM\ManyToOne(targetEntity="ProductKitItem", inversedBy="labels")
     * @ORM\JoinColumn(name="product_kit_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?ProductKitItem $productKitItem = null;

    public function getProductKitItem(): ?ProductKitItem
    {
        return $this->productKitItem;
    }

    public function setProductKitItem(?ProductKitItem $productKitItem): self
    {
        $this->productKitItem = $productKitItem;

        return $this;
    }
}
