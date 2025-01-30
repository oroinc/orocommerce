<?php

namespace Oro\Bundle\WebCatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroWebCatalogBundle_Entity_ContentNode;
use Gedmo\Mapping\Annotation as Gedmo;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeWithRedirectAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeWithRedirectAwareTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeCollectionAwareInterface;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Component\Tree\Entity\TreeTrait;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface;

/**
 * Represents a node in the web catalog tree.
 *
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method LocalizedFallbackValue getSlugPrototype(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultSlugPrototype()
 * @method setDefaultTitle($title)
 * @method setDefaultSlugPrototype($slug)
 * @method $this cloneLocalizedFallbackValueAssociations()
 * @mixin OroWebCatalogBundle_Entity_ContentNode
 */
#[ORM\Entity(repositoryClass: ContentNodeRepository::class)]
#[ORM\Table(name: 'oro_web_catalog_content_node')]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'slugPrototypes',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'node_id',
                referencedColumnName: 'id',
                onDelete: 'CASCADE'
            )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'localized_value_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_web_catalog_node_slug_prot')
    )
])]
#[Gedmo\Tree(type: 'nested')]
#[Config(
    defaultValues: [
        'dataaudit' => ['auditable' => true],
        'activity' => [
            'show_on_page' => ActivityScope::UPDATE_PAGE
        ],
        'slug' => ['source' => 'titles']
    ]
)]
class ContentNode implements
    ContentNodeInterface,
    DatesAwareInterface,
    LocalizedSlugPrototypeWithRedirectAwareInterface,
    ScopeCollectionAwareInterface,
    WebCatalogAwareInterface,
    ExtendEntityInterface
{
    use TreeTrait;
    use DatesAwareTrait;
    use LocalizedSlugPrototypeWithRedirectAwareTrait;
    use ExtendEntityTrait;

    const FIELD_PARENT_NODE = 'parentNode';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContentNode::class, inversedBy: 'childNodes')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ContentNode $parentNode = null;

    /**
     * @var Collection<int, ContentNode>
     */
    #[ORM\OneToMany(mappedBy: 'parentNode', targetEntity: ContentNode::class, cascade: ['persist'])]
    #[ORM\OrderBy(['left' => Criteria::ASC])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $childNodes = null;

    #[ORM\Column(name: 'parent_scope_used', type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $parentScopeUsed = true;

    #[ORM\Column(name: 'rewrite_variant_title', type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $rewriteVariantTitle = true;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_web_catalog_node_title')]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $titles = null;

    /**
     * @var Collection<int, Scope>
     */
    #[ORM\ManyToMany(targetEntity: Scope::class)]
    #[ORM\JoinTable(name: 'oro_web_catalog_node_scope')]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $scopes = null;

    /**
     * @var Collection<int, ContentVariant>
     */
    #[ORM\OneToMany(mappedBy: 'node', targetEntity: ContentVariant::class, cascade: ['ALL'], orphanRemoval: true)]
    protected ?Collection $contentVariants = null;

    #[ORM\Column(name: 'materialized_path', type: Types::STRING, length: 1024, nullable: true)]
    protected ?string $materializedPath = null;

    #[ORM\ManyToOne(targetEntity: WebCatalog::class)]
    #[ORM\JoinColumn(name: 'web_catalog_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?WebCatalog $webCatalog = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_web_catalog_node_url')]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $localizedUrls = null;

    /**
     * Property used by {@see \Gedmo\Tree\Entity\Repository\NestedTreeRepository::__call}
     * @var self|null
     */
    public $sibling;

    /**
     * @var Collection<int, MenuUpdate>
     */
    #[ORM\OneToMany(mappedBy: 'contentNode', targetEntity: MenuUpdate::class)]
    private ?Collection $referencedMenuItems = null;

    /**
     * @var
     * @var Collection|Consent[]
     */
    #[ORM\OneToMany(mappedBy: 'contentNode', targetEntity: Consent::class)]
    private ?Collection $referencedConsents = null;

    /**
     * ContentNode Constructor
     */
    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->childNodes = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->contentVariants = new ArrayCollection();
        $this->localizedUrls = new ArrayCollection();
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);

        $this->referencedMenuItems = new ArrayCollection();
        $this->referencedConsents = new ArrayCollection();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getDefaultTitle();
    }

    /**
     * @return int
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ContentNode
     */
    public function getParentNode()
    {
        return $this->parentNode;
    }

    /**
     * @param ContentNode|null $parentNode
     *
     * @return $this
     */
    public function setParentNode(?ContentNode $parentNode = null)
    {
        $this->parentNode = $parentNode;

        return $this;
    }

    /**
     * @return Collection|ContentNode[]
     */
    public function getChildNodes()
    {
        return $this->childNodes;
    }

    /**
     * @param ContentNode $node
     *
     * @return $this
     */
    public function addChildNode(ContentNode $node)
    {
        if (!$this->childNodes->contains($node)) {
            $this->childNodes->add($node);
            $node->setParentNode($this);
        }

        return $this;
    }

    /**
     * @param ContentNode $node
     *
     * @return $this
     */
    public function removeChildNode(ContentNode $node)
    {
        if ($this->childNodes->contains($node)) {
            $this->childNodes->removeElement($node);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    #[\Override]
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function addTitle(LocalizedFallbackValue $title)
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function removeTitle(LocalizedFallbackValue $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return Collection|Scope[]
     */
    #[\Override]
    public function getScopes()
    {
        return $this->scopes;
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
     * @return $this
     */
    public function resetScopes()
    {
        $this->scopes->clear();

        return $this;
    }

    /**
     * @return Collection|ContentVariant[]
     */
    #[\Override]
    public function getContentVariants()
    {
        return $this->contentVariants;
    }

    /**
     * @param ContentVariant $contentVariant
     *
     * @return $this
     */
    public function addContentVariant(ContentVariant $contentVariant)
    {
        if (!$this->contentVariants->contains($contentVariant)) {
            $contentVariant->setNode($this);
            $this->contentVariants->add($contentVariant);
        }

        return $this;
    }

    /**
     * @param ContentVariant $page
     *
     * @return $this
     */
    public function removeContentVariant(ContentVariant $page)
    {
        if ($this->contentVariants->contains($page)) {
            $this->contentVariants->removeElement($page);
        }

        return $this;
    }

    /**
     * @return ContentVariant|null
     */
    public function getDefaultVariant()
    {
        foreach ($this->contentVariants as $variant) {
            if ($variant->isDefault()) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getMaterializedPath()
    {
        return $this->materializedPath;
    }

    /**
     * @param string $materializedPath
     *
     * @return $this
     */
    public function setMaterializedPath($materializedPath)
    {
        $this->materializedPath = $materializedPath;

        return $this;
    }

    /**
     * @return WebCatalog
     */
    #[\Override]
    public function getWebCatalog()
    {
        return $this->webCatalog;
    }

    /**
     * @param WebCatalog $webCatalog
     * @return $this
     */
    public function setWebCatalog(WebCatalog $webCatalog)
    {
        $this->webCatalog = $webCatalog;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isParentScopeUsed()
    {
        return $this->parentScopeUsed;
    }

    /**
     * @param boolean $parentScopeUsed
     * @return $this
     */
    public function setParentScopeUsed($parentScopeUsed)
    {
        $this->parentScopeUsed = $parentScopeUsed;

        return $this;
    }

    /**
     * @return boolean
     */
    #[\Override]
    public function isRewriteVariantTitle()
    {
        return $this->rewriteVariantTitle;
    }

    /**
     * @param boolean $rewriteVariantTitle
     * @return $this
     */
    public function setRewriteVariantTitle($rewriteVariantTitle)
    {
        $this->rewriteVariantTitle = $rewriteVariantTitle;

        return $this;
    }

    /**
     * @return Collection|Scope[]
     */
    public function getScopesConsideringParent()
    {
        return $this->getScopesWithFallback($this);
    }

    /**
     * @param ContentNode $contentNode
     * @return Collection|Scope[]
     */
    protected function getScopesWithFallback(ContentNode $contentNode)
    {
        $parentNode = $contentNode->getParentNode();
        if ($parentNode && $contentNode->isParentScopeUsed()) {
            return $this->getScopesWithFallback($parentNode);
        } else {
            return $contentNode->getScopes();
        }
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLocalizedUrls()
    {
        return $this->localizedUrls;
    }

    /**
     * @param LocalizedFallbackValue $url
     * @return $this
     */
    public function addLocalizedUrl(LocalizedFallbackValue $url)
    {
        if (!$this->hasLocalizedUrl($url)) {
            $this->localizedUrls->add($url);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $url
     * @return $this
     */
    public function removeLocalizedUrl(LocalizedFallbackValue $url)
    {
        if ($this->hasLocalizedUrl($url)) {
            $this->localizedUrls->removeElement($url);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $url
     * @return bool
     */
    public function hasLocalizedUrl(LocalizedFallbackValue $url)
    {
        return $this->localizedUrls->contains($url);
    }

    public function getReferencedMenuItems(): Collection
    {
        return $this->referencedMenuItems;
    }

    /**
     * @param Collection<int, MenuUpdate> $referencedMenuItems
     */
    public function setReferencedMenuItems(Collection $referencedMenuItems): void
    {
        $this->referencedMenuItems = $referencedMenuItems;
    }

    public function getReferencedConsents(): Collection
    {
        return $this->referencedConsents;
    }

    /**
     * @param Collection<int, Consent> $referencedConsents
     */
    public function setReferencedConsents(Collection $referencedConsents): void
    {
        $this->referencedConsents = $referencedConsents;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->cloneExtendEntityStorage();
            $this->cloneLocalizedFallbackValueAssociations();
        }
    }
}
