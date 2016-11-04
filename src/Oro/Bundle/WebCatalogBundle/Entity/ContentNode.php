<?php

namespace Oro\Bundle\WebCatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Model\ExtendContentNode;
use Oro\Component\Tree\Entity\TreeTrait;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository")
 * @ORM\Table(name="oro_web_catalog_content_node")
 * @Gedmo\Tree(type="nested")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "activity"={
 *              "show_on_page"="\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE"
 *          }
 *     }
 * )
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ContentNode extends ExtendContentNode implements ContentNodeInterface, DatesAwareInterface
{
    use TreeTrait;
    use DatesAwareTrait;

    const FIELD_PARENT_NODE = 'parentNode';
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ContentNode
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="ContentNode", inversedBy="childNodes")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $parentNode;

    /**
     * @var Collection|ContentNode[]
     *
     * @ORM\OneToMany(targetEntity="ContentNode", mappedBy="parentNode", cascade={"persist", "remove"})
     * @ORM\OrderBy({"left" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $childNodes;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_web_catalog_node_title",
     *      joinColumns={
     *          @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $titles;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_web_catalog_node_slug_prot",
     *      joinColumns={
     *          @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $slugPrototypes;

    /**
     * @var Collection|Slug[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\RedirectBundle\Entity\Slug",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(name="oro_web_catalog_node_slug",
     *      joinColumns={
     *          @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="slug_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
     *      }
     * )
     */
    protected $slugs;

    /**
     * @var Collection|ContentVariant[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\WebCatalogBundle\Entity\ContentVariant",
     *     mappedBy="node",
     *     cascade={"persist"}
     * )
     */
    protected $contentVariants;

    /**
     * @var string
     *
     * @ORM\Column(name="materialized_path", type="string", length=1024, nullable=true)
     */
    protected $materializedPath;

    /**
     * @var WebCatalog
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebCatalogBundle\Entity\WebCatalog")
     * @ORM\JoinColumn(name="web_catalog_id", referencedColumnName="id",onDelete="CASCADE",nullable=false)
     */
    protected $webCatalog;

    /**
     * ContentNode Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
        $this->childNodes = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->contentVariants = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getDefaultTitle();
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ContentNode
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
    public function setParentNode(ContentNode $parentNode = null)
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
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getSlugPrototypes()
    {
        return $this->slugPrototypes;
    }

    /**
     * @param LocalizedFallbackValue $slug
     *
     * @return $this
     */
    public function addSlugPrototype(LocalizedFallbackValue $slug)
    {
        if (!$this->slugPrototypes->contains($slug)) {
            $this->slugPrototypes->add($slug);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $slug
     *
     * @return $this
     */
    public function removeSlugPrototype(LocalizedFallbackValue $slug)
    {
        if ($this->slugPrototypes->contains($slug)) {
            $this->slugPrototypes->removeElement($slug);
        }

        return $this;
    }

    /**
     * @return Collection|Slug[]
     */
    public function getSlugs()
    {
        return $this->slugs;
    }

    /**
     * @param Slug $slug
     * @return $this
     */
    public function addSlug(Slug $slug)
    {
        if (!$this->slugs->contains($slug)) {
            $this->slugs->add($slug);
        }
        return $this;
    }

    /**
     * @param Slug $slug
     * @return $this
     */
    public function removeSlug(Slug $slug)
    {
        if ($this->slugs->contains($slug)) {
            $this->slugs->removeElement($slug);
        }
        return $this;
    }

    /**
     * @return Collection|ContentVariant[]
     */
    public function getContentVariants()
    {
        return $this->contentVariants;
    }

    /**
     * @param ContentVariant $page
     *
     * @return $this
     */
    public function addContentVariant(ContentVariant $page)
    {
        if (!$this->contentVariants->contains($page)) {
            $this->contentVariants->add($page);
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
}
