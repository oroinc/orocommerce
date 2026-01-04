<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeAwareInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;

/**
 * The entity to store configured product visibility rules.
 */
#[ORM\Entity(repositoryClass: ProductVisibilityRepository::class)]
#[ORM\Table(name: 'oro_product_visibility')]
#[ORM\UniqueConstraint(name: 'oro_prod_vis_uidx', columns: ['product_id', 'scope_id'])]
#[Config]
class ProductVisibility implements VisibilityInterface, ScopeAwareInterface
{
    public const CATEGORY = 'category';
    public const CONFIG = 'config';
    public const VISIBILITY_TYPE = 'product_visibility';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Scope::class)]
    #[ORM\JoinColumn(name: 'scope_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Scope $scope = null;

    #[ORM\Column(name: 'visibility', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $visibility = null;

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @param Product $product
     * @return string
     */
    #[\Override]
    public static function getDefault($product)
    {
        return self::CATEGORY;
    }

    #[\Override]
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    #[\Override]
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param Product $product
     * @return array
     */
    #[\Override]
    public static function getVisibilityList($product)
    {
        if (EntityPropertyInfo::methodExists($product, 'getCategory') && !$product->getCategory()) {
            return [
                self::CONFIG,
                self::HIDDEN,
                self::VISIBLE
            ];
        }

        return [
            self::CATEGORY,
            self::CONFIG,
            self::HIDDEN,
            self::VISIBLE
        ];
    }

    #[\Override]
    public function getTargetEntity()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    #[\Override]
    public function setTargetEntity($product)
    {
        $this->setProduct($product);

        return $this;
    }

    /**
     * @return Scope
     */
    #[\Override]
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    #[\Override]
    public function setScope(Scope $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    #[\Override]
    public static function getScopeType()
    {
        return self::VISIBILITY_TYPE;
    }
}
