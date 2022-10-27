<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents category short description
 *
 * @ORM\Table(
 *      name="oro_catalog_cat_s_descr",
 *      indexes={
 *          @ORM\Index(name="idx_cat_cat_s_descr_fallback", columns={"fallback"})
 *      }
 * )
 * @ORM\Entity
 * @Config()
 */
class CategoryShortDescription extends AbstractLocalizedFallbackValue
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="text", type="text", nullable=true)
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
    protected $text;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="shortDescriptions")
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
}
