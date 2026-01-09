<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\Id\UuidGenerator;

/**
 * Stores relation between price list and product entities
 * with additional information about type of assignment (auto or manual).
 */
#[ORM\Entity(repositoryClass: PriceListToProductRepository::class)]
#[ORM\Table(name: 'oro_price_list_to_product')]
#[ORM\UniqueConstraint(name: 'oro_price_list_to_product_uidx', columns: ['product_id', 'price_list_id'])]
class PriceListToProduct
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'id', type: Types::GUID)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected $id;

    #[ORM\ManyToOne(targetEntity: PriceList::class)]
    #[ORM\JoinColumn(name: 'price_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?PriceList $priceList = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Product $product = null;

    #[ORM\Column(name: 'is_manual', type: Types::BOOLEAN)]
    protected ?bool $manual = true;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param PriceList $priceList
     * @return $this
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;

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
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isManual()
    {
        return $this->manual;
    }

    /**
     * @param bool $manual
     * @return $this
     */
    public function setManual($manual)
    {
        $this->manual = $manual;

        return $this;
    }
}
