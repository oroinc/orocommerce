<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository;

/**
 * Represents relation suggestion to products
 */
#[ORM\Entity(repositoryClass: ProductSuggestionRepository::class)]
#[ORM\Table(name: 'oro_website_search_suggestion_product')]
#[ORM\UniqueConstraint(name: 'product_suggestion_unique', columns: ['suggestion_id', 'product_id'])]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id',
        ],
        'dataaudit' => ['auditable' => false],
    ],
)]
class ProductSuggestion implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Product $product;

    #[ORM\ManyToOne(targetEntity: Suggestion::class)]
    #[ORM\JoinColumn(name: 'suggestion_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Suggestion $suggestion;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setSuggestion(Suggestion $suggestion): void
    {
        $this->suggestion = $suggestion;
    }

    public function getSuggestion(): Suggestion
    {
        return $this->suggestion;
    }
}
