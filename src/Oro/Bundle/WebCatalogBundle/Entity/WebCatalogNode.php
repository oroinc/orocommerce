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
use Oro\Bundle\WebCatalogBundle\Model\ExtendWebCatalogNode;
use Oro\Component\Tree\Entity\TreeTrait;
use Oro\Component\WebCatalog\Entity\WebCatalogNodeInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_web_catalog_node")
 * @Gedmo\Tree(type="nested")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *     }
 * )
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WebCatalogNode extends ExtendWebCatalogNode implements WebCatalogNodeInterface, DatesAwareInterface
{
    use TreeTrait;
    use DatesAwareTrait;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var WebCatalogNode
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="WebCatalogNode", inversedBy="childNodes")
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
     * @var Collection|WebCatalogNode[]
     *
     * @ORM\OneToMany(targetEntity="WebCatalogNode", mappedBy="parentNode", cascade={"persist"})
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
     *      name="oro_web_catalog_node_slug",
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
    protected $slugs;

    /**
     * @var Collection|Slug[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\RedirectBundle\Entity\Slug",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(name="oro_web_catalog_node_to_slug",
     *      joinColumns={
     *          @ORM\JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="slug_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
     *      }
     * )
     */
    protected $pageSlugs;

    /**
     * @var Collection|WebCatalogPage[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\WebCatalogBundle\Entity\WebCatalogPage", mappedBy="node")
     */
    protected $pages;

    /**
     * @var string
     *
     * @ORM\Column(name="materialized_path", type="string", length=255, nullable=true)
     */
    protected $materializedPath;

    /**
     * WebCatalogNode Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
        $this->childNodes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->pageSlugs = new ArrayCollection();
        $this->pages = new ArrayCollection();
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
     * @return WebCatalogNode
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return WebCatalogNode
     */
    public function getParentNode()
    {
        return $this->parentNode;
    }

    /**
     * @param WebCatalogNode|null $parentNode
     *
     * @return $this
     */
    public function setParentNode(WebCatalogNode $parentNode = null)
    {
        $this->parentNode = $parentNode;

        return $this;
    }

    /**
     * @return Collection|WebCatalogNode[]
     */
    public function getChildNodes()
    {
        return $this->childNodes;
    }

    /**
     * @param WebCatalogNode $node
     *
     * @return $this
     */
    public function addChildNode(WebCatalogNode $node)
    {
        if (!$this->childNodes->contains($node)) {
            $this->childNodes->add($node);
            $node->setParentNode($this);
        }

        return $this;
    }

    /**
     * @param WebCatalogNode $node
     *
     * @return $this
     */
    public function removeChildNode(WebCatalogNode $node)
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
    public function getSlugs()
    {
        return $this->slugs;
    }

    /**
     * @param LocalizedFallbackValue $slug
     *
     * @return $this
     */
    public function addSlug(LocalizedFallbackValue $slug)
    {
        if (!$this->slugs->contains($slug)) {
            $this->slugs->add($slug);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $slug
     *
     * @return $this
     */
    public function removeSlug(LocalizedFallbackValue $slug)
    {
        if ($this->slugs->contains($slug)) {
            $this->slugs->removeElement($slug);
        }

        return $this;
    }

    /**
     * @return Collection|Slug[]
     */
    public function getPageSlugs()
    {
        return $this->pageSlugs;
    }

    /**
     * @param Slug $pageSlug
     *
     * @return $this
     */
    public function addPageSlug(Slug $pageSlug)
    {
        if (!$this->pageSlugs->contains($pageSlug)) {
            $this->pageSlugs->add($pageSlug);
        }

        return $this;
    }

    /**
     * @param Slug $pageSlug
     *
     * @return $this
     */
    public function removePageSlug(Slug $pageSlug)
    {
        if ($this->pageSlugs->contains($pageSlug)) {
            $this->pageSlugs->removeElement($pageSlug);
        }

        return $this;
    }

    /**
     * @return Collection|WebCatalogPage[]
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param WebCatalogPage $page
     *
     * @return $this
     */
    public function addPage(WebCatalogPage $page)
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
        }

        return $this;
    }

    /**
     * @param WebCatalogPage $page
     *
     * @return $this
     */
    public function removePage(WebCatalogPage $page)
    {
        if ($this->pages->contains($page)) {
            $this->pages->removeElement($page);
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
}
