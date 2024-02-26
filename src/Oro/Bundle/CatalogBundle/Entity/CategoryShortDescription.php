<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents category short description
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_catalog_cat_s_descr')]
#[ORM\Index(columns: ['fallback'], name: 'idx_cat_cat_s_descr_fallback')]
#[Config]
class CategoryShortDescription extends AbstractLocalizedFallbackValue
{
    #[ORM\Column(name: 'text', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => false]])]
    protected ?string $text = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'shortDescriptions')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Category $category = null;

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
