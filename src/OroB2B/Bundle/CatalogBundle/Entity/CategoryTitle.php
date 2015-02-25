<?php

namespace OroB2B\Bundle\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @ORM\Table(name="orob2b_catalog_category_title")
 * @ORM\Entity
 * @Config
 */
class CategoryTitle
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
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="titles")
     * @ORM\JoinColumn(name="catalog_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    protected $category;

    /**
     * @var Locale
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Locale")
     * @ORM\JoinColumn(name="locale_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="fallback", type="string", length=64, nullable=true)
     */
    protected $fallback;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Locale|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param Locale|null $locale
     * @return $this
     */
    public function setLocale(Locale $locale = null)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param string $fallback
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }
}
