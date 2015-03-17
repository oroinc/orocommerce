<?php

namespace OroB2B\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;

/**
 * @ORM\Table(name="orob2b_cms_page")
 * @ORM\Entity
 * @Gedmo\Tree(type="nested")
 * @Config(
 *      routeName="orob2b_catalog_category_index",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-folder-close"
 *          },
 *         "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Page
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * @var Slug
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\RedirectBundle\Entity\Slug")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="curent_slug_id", referencedColumnName="id")
     * })
     */
    protected $currentSlug;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $organization;

    /**
     * @var integer
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="tree_left", type="integer")
     */
    protected $left;

    /**
     * @var integer
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="tree_level", type="integer")
     */
    protected $level;

    /**
     * @var integer
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="tree_right", type="integer")
     */
    protected $right;

    /**
     * @var integer
     *
     * @Gedmo\TreeRoot
     * @ORM\Column(name="tree_root", type="integer", nullable=true)
     */
    protected $root;

    /**
     * @var Page
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="childPages")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentPage;

    /**
     * @var Collection|Page[]
     *
     * @ORM\OneToMany(targetEntity="Page", mappedBy="parentPage", cascade={"persist"})
     * @ORM\OrderBy({"left" = "ASC"})
     */
    protected $childPages;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var Collection|Slug[]
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\RedirectBundle\Entity\Slug")
     * @ORM\JoinTable(name="orob2b_cms_page_to_slug",
     *      joinColumns={@ORM\JoinColumn(name="page_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slug_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $slugs;

    public function __construct()
    {
        $this->slugs      = new ArrayCollection();
        $this->childPages = new ArrayCollection();
        $this->createdAt  = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt  = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return Issue
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set current slug
     *
     * @param Slug $curentSlug
     * @return Issue
     */
    public function setCurrentSlug(Slug $currentSlug = null)
    {
        $this->currentSlug = $currentSlug;

        return $this;
    }

    /**
     * Get currentSlug
     *
     * @return Slug
     */
    public function getCurrentSlug()
    {
        return $this->currentSlug;
    }

    /**
     * @return int
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param int $left
     * @return $this
     */
    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return int
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param int $right
     * @return $this
     */
    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    /**
     * @return int
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param int $root
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @return Page
     */
    public function getParentPage()
    {
        return $this->parentPage;
    }

    /**
     * @param Page|null $parentPage
     * @return $this
     */
    public function setParentPage(Page $parentPage = null)
    {
        $this->parentPage = $parentPage;

        return $this;
    }

    /**
     * @return Collection|Page[]
     */
    public function getChildPages()
    {
        return $this->childPages;
    }

    /**
     * @param Page $page
     * @return $this
     */
    public function addChildPage(Page $page)
    {
        if (!$this->childPages->contains($page)) {
            $this->childPages->add($page);
            $page->setParentPage($this);
        }

        return $this;
    }

    /**
     * @param Page $Page
     * @return $this
     */
    public function removeChildPage(Page $page)
    {
        if ($this->childPages->contains($page)) {
            $this->childPages->removeElement($page);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

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
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }
}
