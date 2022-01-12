<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents category long description
 *
 * @ORM\Table(
 *      name="oro_catalog_cat_l_descr",
 *      indexes={
 *          @ORM\Index(name="idx_cat_cat_l_descr_fallback", columns={"fallback"})
 *      }
 * )
 * @ORM\Entity
 * @Config()
 */
class CategoryLongDescription extends AbstractLocalizedFallbackValue
{
    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="longDescriptions")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $category;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param null|Category $category
     * @return $this
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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
