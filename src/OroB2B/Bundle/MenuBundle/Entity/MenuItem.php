<?php

namespace OroB2B\Bundle\MenuBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\MenuBundle\Model\ExtendMenuItem;
use OroB2B\Component\Tree\Entity\TreeTrait;

/**
 * @ORM\Table(name="orob2b_menu_item")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository")
 * @Gedmo\Tree(type="nested")
 * @Config(
 *      routeName="orob2b_menu_item_index",
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
     *      targetEntity="OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue",
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
     * @ORM\ManyToOne(targetEntity="MenuItem")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentMenuItem;

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
     * @var string
     *
     * @ORM\Column(name="mi_condition", type="text", nullable=true)
     */
    protected $condition;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
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
     * @return LocalizedFallbackValue
     */
    public function getDefaultTitle()
    {
        $titles = $this->titles->filter(
            function (LocalizedFallbackValue $title) {
                return null === $title->getLocale();
            }
        );

        if ($titles->count() > 1) {
            throw new \LogicException('There must be only one default title');
        }

        return $titles->first();
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
    public function getParentMenuItem()
    {
        return $this->parentMenuItem;
    }

    /**
     * @param MenuItem|null $parentMenuItem
     * @return $this
     */
    public function setParentMenuItem(MenuItem $parentMenuItem = null)
    {
        $this->parentMenuItem = $parentMenuItem;

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
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $route
     * @return $this
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * @param array $routeParameters
     * @return $this
     */
    public function setRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;

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
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     * @return $this
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }
}
