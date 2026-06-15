<?php

namespace Oro\Bundle\InventoryBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroInventoryBundle_Entity_InventoryLevel;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Represents inventory level (the current amount of a product that a business has in stock)
 *
 * @mixin OroInventoryBundle_Entity_InventoryLevel
 */
#[ORM\Entity(repositoryClass: InventoryLevelRepository::class)]
#[ORM\Table(name: 'oro_inventory_level')]
#[ORM\UniqueConstraint(
    name: 'oro_inventory_level_unique_index',
    columns: ['product_id', 'product_unit_precision_id', 'organization_id']
)]
#[Config(
    defaultValues: [
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'email' => ['available_in_template' => true],
    ]
)]
class InventoryLevel implements
    OrganizationAwareInterface,
    ExtendEntityInterface
{
    use OrganizationAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?int $id = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: Types::DECIMAL, precision: 20, scale: 10, nullable: false)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected $quantity = 0;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: ProductUnitPrecision::class)]
    #[ORM\JoinColumn(
        name: 'product_unit_precision_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?ProductUnitPrecision $productUnitPrecision = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return InventoryLevel
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return ProductUnitPrecision
     */
    public function getProductUnitPrecision()
    {
        return $this->productUnitPrecision;
    }

    /**
     * @param ProductUnitPrecision $productUnitPrecision
     * @return InventoryLevel
     */
    public function setProductUnitPrecision(ProductUnitPrecision $productUnitPrecision)
    {
        $this->productUnitPrecision = $productUnitPrecision;
        $this->product = $productUnitPrecision->getProduct();

        return $this;
    }
}
