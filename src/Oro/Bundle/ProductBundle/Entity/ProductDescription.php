<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents product description
 *
 * @ORM\Table(
 *      name="oro_product_prod_descr",
 *      indexes={
 *          @ORM\Index(name="idx_product_prod_descr_fallback", columns={"fallback"})
 *      }
 * )
 * @ORM\Entity
 * @Config()
 */
class ProductDescription extends AbstractLocalizedFallbackValue
{
    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="descriptions")
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

    /**
     * @var null|string
     *
     * @ORM\Column(type="wysiwyg", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "attachment"={
     *              "acl_protected"=true,
     *          }
     *      }
     * )
     */
    protected $wysiwyg;

    /**
     * @var null|string
     *
     * @ORM\Column(type="wysiwyg_style", name="wysiwyg_style", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "attachment"={
     *              "acl_protected"=true,
     *          }
     *      }
     * )
     */
    protected $wysiwygStyle;

    /**
     * @var null|array
     *
     * @ORM\Column(type="wysiwyg_properties", name="wysiwyg_properties", nullable=true)
     */
    protected $wysiwygProperties;

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

    public function getWysiwyg(): ?string
    {
        return $this->wysiwyg;
    }

    /**
     * @param null|string $wysiwyg
     * @return $this
     */
    public function setWysiwyg(?string $wysiwyg): self
    {
        $this->wysiwyg = $wysiwyg;

        return $this;
    }

    public function getWysiwygStyle(): ?string
    {
        return $this->wysiwygStyle;
    }

    /**
     * @param null|string $wysiwygStyle
     * @return $this
     */
    public function setWysiwygStyle(?string $wysiwygStyle): self
    {
        $this->wysiwygStyle = $wysiwygStyle;

        return $this;
    }

    public function getWysiwygProperties(): ?array
    {
        return $this->wysiwygProperties;
    }

    /**
     * @param null|array $wysiwygProperties
     * @return $this
     */
    public function setWysiwygProperties(?array $wysiwygProperties): self
    {
        $this->wysiwygProperties = $wysiwygProperties;

        return $this;
    }
}
