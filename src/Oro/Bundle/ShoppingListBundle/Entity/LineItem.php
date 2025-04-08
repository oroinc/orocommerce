<?php

namespace Oro\Bundle\ShoppingListBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Extend\Entity\Autocomplete\OroShoppingListBundle_Entity_LineItem;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorOwnerAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemChecksumAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * Represents a line item in a shopping list.
 *
 * @mixin OroShoppingListBundle_Entity_LineItem
 */
#[ORM\Entity(repositoryClass: LineItemRepository::class)]
#[ORM\Table(name: 'oro_shopping_list_line_item')]
#[ORM\UniqueConstraint(
    name: 'oro_shopping_list_line_item_uidx',
    columns: ['product_id', 'shopping_list_id', 'unit_code', 'checksum']
)]
#[Config(
    defaultValues: [
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'shopping'],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'frontend_owner_type' => 'FRONTEND_USER',
            'frontend_owner_field_name' => 'customerUser',
            'frontend_owner_column_name' => 'customer_user_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'dataaudit' => ['auditable' => true],
        'entity' => ['icon' => 'fa-shopping-cart']
    ]
)]
class LineItem implements
    OrganizationAwareInterface,
    CustomerVisitorOwnerAwareInterface,
    ProductLineItemInterface,
    ProductLineItemChecksumAwareInterface,
    ProductKitItemLineItemsAwareInterface,
    ProductLineItemsHolderAwareInterface,
    ExtendEntityInterface
{
    use UserAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'parent_product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Product $parentProduct = null;

    /**
     * @var Collection<int, ProductKitItemLineItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'lineItem',
        targetEntity: ProductKitItemLineItem::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    #[OrderBy(['sortOrder' => Criteria::ASC])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $kitItemLineItems = null;

    /**
     * Differentiates the unique constraint allowing to add the same product with the same unit code multiple times,
     * moving the logic of distinguishing of such line items out of the entity class.
     */
    #[ORM\Column(name: 'checksum', type: Types::STRING, length: 40, nullable: false, options: ['default' => ''])]
    protected ?string $checksum = '';

    #[ORM\ManyToOne(targetEntity: ShoppingList::class, inversedBy: 'lineItems')]
    #[ORM\JoinColumn(name: 'shopping_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ShoppingList $shoppingList = null;

    /**
     * @var int|float
     */
    #[ORM\Column(name: 'quantity', type: Types::FLOAT)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected $quantity = 1;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'unit_code', referencedColumnName: 'code', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ProductUnit $unit = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(name: 'customer_user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?CustomerUser $customerUser = null;

    public function __construct()
    {
        $this->kitItemLineItems = new ArrayCollection();
    }

    /**
     * @return integer
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
     * @return Product|null
     */
    public function getParentProduct()
    {
        return $this->parentProduct;
    }

    /**
     * @param Product $parentProduct
     *
     * @return $this
     */
    public function setParentProduct(Product $parentProduct)
    {
        $this->parentProduct = $parentProduct;

        return $this;
    }

    /**
     * @return ShoppingList
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @param ShoppingList $shoppingList
     *
     * @return $this
     */
    public function setShoppingList(ShoppingList $shoppingList)
    {
        $this->shoppingList = $shoppingList;

        return $this;
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
     *
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit $unit
     *
     * @return $this
     */
    public function setUnit(ProductUnit $unit)
    {
        $this->unit = $unit;

        return $this;
    }

    public function setProductUnit(ProductUnit $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     *
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return CustomerUser|null
     */
    public function getCustomerUser()
    {
        return $this->customerUser;
    }

    /**
     * @param CustomerUser|null $user
     *
     * @return $this
     */
    public function setCustomerUser(?CustomerUser $user = null)
    {
        $this->customerUser = $user;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnit()
    {
        return $this->getUnit();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode()
    {
        $unit = $this->getUnit();
        if (!$unit) {
            return null;
        }

        return $unit->getCode();
    }

    /** {@inheritdoc} */
    public function getProductSku()
    {
        $product = $this->getProduct();
        if ($product) {
            return $product->getSku();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisitor()
    {
        return $this->getShoppingList()->getVisitor();
    }

    /**
     * @return Collection<ProductKitItemLineItem>
     */
    public function getKitItemLineItems()
    {
        return $this->kitItemLineItems;
    }

    public function addKitItemLineItem(ProductKitItemLineItem $productKitItemLineItem): self
    {
        if (!$this->kitItemLineItems->contains($productKitItemLineItem)) {
            $productKitItemLineItem->setLineItem($this);
            $this->kitItemLineItems->add($productKitItemLineItem);
        }

        return $this;
    }

    public function removeKitItemLineItem(ProductKitItemLineItem $productKitItemLineItem): self
    {
        $this->kitItemLineItems->removeElement($productKitItemLineItem);

        return $this;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getLineItemsHolder(): ?ProductLineItemsHolderInterface
    {
        return $this->shoppingList;
    }
}
