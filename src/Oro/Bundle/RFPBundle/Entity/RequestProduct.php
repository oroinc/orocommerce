<?php

namespace Oro\Bundle\RFPBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;

/**
 * RFP Request Product entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_rfp_request_product')]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-list-alt'],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'quotes']
    ]
)]
class RequestProduct implements ProductHolderInterface, ProductKitItemLineItemsAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Request::class, inversedBy: 'requestProducts')]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Request $request = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Product $product = null;

    #[ORM\Column(name: 'product_sku', type: Types::STRING, length: 255)]
    protected ?string $productSku = null;

    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    protected ?string $comment = null;

    /**
     * @var Collection<int, RequestProductItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'requestProduct',
        targetEntity: RequestProductItem::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    protected ?Collection $requestProductItems = null;

    /**
     * @var Collection<RequestProductKitItemLineItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'requestProduct',
        targetEntity: RequestProductKitItemLineItem::class,
        cascade: ['ALL'],
        orphanRemoval: true,
        indexBy: 'kitItemId'
    )]
    #[OrderBy(['sortOrder' => 'ASC'])]
    protected $kitItemLineItems;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requestProductItems = new ArrayCollection();
        $this->kitItemLineItems = new ArrayCollection();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->productSku;
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->getId();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set request
     *
     * @param Request|null $request
     * @return RequestProduct
     */
    public function setRequest(?Request $request = null)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set product
     *
     * @param Product|null $product
     * @return RequestProduct
     */
    public function setProduct(?Product $product = null)
    {
        $this->product = $product;
        if ($product) {
            $this->productSku = $product->getSku();
        }

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set productSku
     *
     * @param string $productSku
     * @return RequestProduct
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;

        return $this;
    }

    /**
     * Get productSku
     *
     * @return string
     */
    #[\Override]
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * @param string $comment
     * @return RequestProduct
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Add requestProductItem
     *
     * @param RequestProductItem $requestProductItem
     * @return RequestProduct
     */
    public function addRequestProductItem(RequestProductItem $requestProductItem)
    {
        if (!$this->requestProductItems->contains($requestProductItem)) {
            $this->requestProductItems[] = $requestProductItem;
            $requestProductItem->setRequestProduct($this);
        }

        return $this;
    }

    /**
     * Remove requestProductItem
     *
     * @param RequestProductItem $requestProductItem
     * @return RequestProduct
     */
    public function removeRequestProductItem(RequestProductItem $requestProductItem)
    {
        if ($this->requestProductItems->contains($requestProductItem)) {
            $this->requestProductItems->removeElement($requestProductItem);
        }

        return $this;
    }

    /**
     * Get requestProductItems
     *
     * @return Collection|RequestProductItem[]
     */
    public function getRequestProductItems()
    {
        return $this->requestProductItems;
    }

    /**
     * @return Collection<RequestProductKitItemLineItem>
     */
    #[\Override]
    public function getKitItemLineItems()
    {
        return $this->kitItemLineItems;
    }

    public function addKitItemLineItem(RequestProductKitItemLineItem $productKitItemLineItem): self
    {
        $index = $productKitItemLineItem->getKitItemId();

        if (!$this->kitItemLineItems->containsKey($index)) {
            $productKitItemLineItem->setRequestProduct($this);
            if ($index) {
                $this->kitItemLineItems->set($index, $productKitItemLineItem);
            } else {
                $this->kitItemLineItems->add($productKitItemLineItem);
            }
        }

        return $this;
    }

    public function removeKitItemLineItem(RequestProductKitItemLineItem $productKitItemLineItem): self
    {
        $this->kitItemLineItems->removeElement($productKitItemLineItem);

        return $this;
    }
}
