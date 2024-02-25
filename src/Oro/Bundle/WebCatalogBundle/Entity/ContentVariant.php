<?php

namespace Oro\Bundle\WebCatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroWebCatalogBundle_Entity_ContentVariant;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareTrait;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeCollectionAwareInterface;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Entity that represents webcatalog content variants
 *
 * @mixin OroWebCatalogBundle_Entity_ContentVariant
 */
#[ORM\Entity(repositoryClass: ContentVariantRepository::class)]
#[ORM\Table(name: 'oro_web_catalog_variant')]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'slugs',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'content_variant_id',
            referencedColumnName: 'id',
            onDelete: 'CASCADE'
        )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'slug_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_web_catalog_variant_slug')
    )
])]
#[Config]
class ContentVariant implements
    ContentVariantInterface,
    ContentNodeAwareInterface,
    SlugAwareInterface,
    ScopeCollectionAwareInterface,
    ExtendEntityInterface
{
    use SlugAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    protected ?string $type = null;

    #[ORM\Column(name: 'system_page_route', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $systemPageRoute = null;

    #[ORM\ManyToOne(targetEntity: ContentNode::class, inversedBy: 'contentVariants')]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ContentNode $node = null;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $default = false;

    /**
     * @var Collection<int, Scope>
     */
    #[ORM\ManyToMany(targetEntity: Scope::class)]
    #[ORM\JoinTable(name: 'oro_web_catalog_variant_scope')]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $scopes = null;

    #[ORM\Column(name: 'override_variant_configuration', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $overrideVariantConfiguration = false;

    /**
     * @var boolean
     */
    protected $expanded = false;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->scopes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getSystemPageRoute()
    {
        return $this->systemPageRoute;
    }

    /**
     * @param string $systemPageRoute
     *
     * @return $this
     */
    public function setSystemPageRoute($systemPageRoute)
    {
        $this->systemPageRoute = $systemPageRoute;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param ContentNode $node
     *
     * @return $this
     */
    public function setNode(ContentNode $node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @return Collection|Scope[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @return $this
     */
    public function resetScopes()
    {
        $this->scopes->clear();

        return $this;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function addScope(Scope $scope)
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function removeScope(Scope $scope)
    {
        if ($this->scopes->contains($scope)) {
            $this->scopes->removeElement($scope);
        }

        return $this;
    }

    /**
     * @param bool $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param bool $overrideVariantConfiguration
     * @return $this
     */
    public function setOverrideVariantConfiguration($overrideVariantConfiguration)
    {
        $this->overrideVariantConfiguration = $overrideVariantConfiguration;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOverrideVariantConfiguration()
    {
        return $this->overrideVariantConfiguration;
    }

    /**
     * @param bool|mixed $expanded
     * @return $this
     */
    public function setExpanded($expanded)
    {
        $this->expanded = (bool) $expanded;

        return $this;
    }

    public function isExpanded(): bool
    {
        return $this->expanded;
    }
}
