<?php

namespace OroB2B\Bundle\MenuBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

use OroB2B\Bundle\MenuBundle\Model\ExtendMenuItem;
use OroB2B\Component\Tree\Entity\TreeTrait;

/**
 * @ORM\Table(name="orob2b_menu_item")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository")
 * @ORM\EntityListeners({ "OroB2B\Bundle\MenuBundle\Entity\Listener\MenuItemListener" })
 * @Gedmo\Tree(type="nested")
 * @Config(
 *      routeName="orob2b_menu_item_roots",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-th"
 *          }
 *      }
 * )
 */
class MenuItem extends ExtendMenuItem
{
    use TreeTrait;
    use FallbackTrait;

    const LOCALE_OPTION = 'orob2b_website_locale';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="orob2b_menu_item_title",
     *      joinColumns={
     *          @ORM\JoinColumn(name="menu_item_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @todo check unique for root
     */
    protected $titles;

    /**
     * @var MenuItem
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="MenuItem", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var Collection|MenuItem[]
     *
     * @ORM\OneToMany(targetEntity="MenuItem", mappedBy="parent", cascade={"persist"})
     * @ORM\OrderBy({"left" = "ASC"})
     */
    protected $children;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="text", nullable=true)
     */
    protected $uri;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display", type="boolean")
     */
    protected $display = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display_children", type="boolean")
     */
    protected $displayChildren = true;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="array")
     */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @param Localization|null $localization
     * @return LocalizedFallbackValue
     */
    public function getTitle(Localization $localization = null)
    {
        return $this->getLocalizedFallbackValue($this->titles, $localization);
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultTitle()
    {
        return $this->getLocalizedFallbackValue($this->titles);
    }

    /**
     * @param $string
     * @return $this
     */
    public function setDefaultTitle($string)
    {
        $oldTitle = $this->getDefaultTitle();
        if ($oldTitle) {
            $this->removeTitle($oldTitle);
        }
        $newTitle = new LocalizedFallbackValue();
        $newTitle->setString($string);
        $this->addTitle($newTitle);

        return $this;
    }

    /**
     * @return MenuItem
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param MenuItem|null $parent
     * @return $this
     */
    public function setParent(MenuItem $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param boolean $display
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getDisplayChildren()
    {
        return $this->displayChildren;
    }

    /**
     * @param boolean $displayChildren
     * @return $this
     */
    public function setDisplayChildren($displayChildren)
    {
        $this->displayChildren = $displayChildren;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtras()
    {
        return array_key_exists('extras', $this->data) ? $this->data['extras'] : [];
    }

    /**
     * @param array $extras
     * @return $this
     */
    public function setExtras(array $extras)
    {
        $this->data['extras'] = $extras;

        return $this;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        $extras = $this->getExtras();

        return array_key_exists('condition', $extras) ? $extras['condition'] : null;
    }

    /**
     * @param string $condition
     * @return $this
     */
    public function setCondition($condition)
    {
        $extras = $this->getExtras();
        $extras['condition'] = $condition;
        if (!$condition) {
            unset($extras['condition']);
        }
        $this->setExtras($extras);

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return Collection|MenuItem[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param MenuItem $item
     * @return $this
     */
    public function addChild(MenuItem $item)
    {
        if (!$this->children->contains($item)) {
            $item->setParent($this);
            $this->children->add($item);
        }

        return $this;
    }
}
