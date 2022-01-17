<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents product name
 *
 * @ORM\Table(
 *      name="oro_product_prod_name",
 *      indexes={
 *          @ORM\Index(name="idx_product_prod_name_fallback", columns={"fallback"}),
 *          @ORM\Index(name="idx_product_prod_name_string", columns={"string"})
 *      }
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class ProductName extends AbstractLocalizedFallbackValue
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="string", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=false
     *          }
     *      }
     * )
     */
    protected $string;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="names")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $product;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param null|Product $product
     * @return $this
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
