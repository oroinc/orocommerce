<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Represents relation suggestion to products
 *
 * @ORM\Table(
 *     name="oro_website_search_suggestion_product",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="product_suggestion_unique",
 *              columns={"suggestion_id", "product_id"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository")
 * @Config(
 *     defaultValues={
 *         "ownership"={
 *             "owner_type"="ORGANIZATION",
 *             "owner_field_name"="organization",
 *             "owner_column_name"="organization_id",
 *         },
 *         "dataaudit"={
 *             "auditable"=false
 *         },
 *     }
 * )
 */
class ProductSuggestion implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected Product $product;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion")
     * @ORM\JoinColumn(name="suggestion_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
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
