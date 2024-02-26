<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroProductBundle_Entity_CollectionSortOrder;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Repository\CollectionSortOrderRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Represents collection sort orders table
 * @mixin OroProductBundle_Entity_CollectionSortOrder
 */
#[ORM\Entity(repositoryClass: CollectionSortOrderRepository::class)]
#[ORM\Table(name: 'oro_product_collection_sort_order')]
#[ORM\UniqueConstraint(name: 'product_segment_sort_uniq_idx', columns: ['product_id', 'segment_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['dataaudit' => ['auditable' => false]])]
class CollectionSortOrder implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'sort_order', type: Types::FLOAT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]])]
    protected $sortOrder;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]])]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Segment::class)]
    #[ORM\JoinColumn(name: 'segment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => false], 'importexport' => ['excluded' => true]])]
    protected ?Segment $segment = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getSegment(): Segment
    {
        return $this->segment;
    }

    public function setSegment(Segment $segment): self
    {
        $this->segment = $segment;
        return $this;
    }

    public function getSortOrder(): ?float
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?float $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }
}
