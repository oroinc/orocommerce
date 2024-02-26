<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents category title
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_catalog_cat_title')]
#[ORM\Index(columns: ['fallback'], name: 'idx_cat_cat_title_fallback')]
#[ORM\Index(columns: ['string'], name: 'idx_cat_cat_title_string')]
#[Config(defaultValues: ['dataaudit' => ['auditable' => true]])]
class CategoryTitle extends AbstractLocalizedFallbackValue
{
    #[ORM\Column(name: 'string', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => false]])]
    protected ?string $string = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'titles')]
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
