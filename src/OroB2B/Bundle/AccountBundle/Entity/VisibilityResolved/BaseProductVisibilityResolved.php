<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @method BaseProductVisibilityResolved setSourceProductVisibility(VisibilityInterface $sourceProductVisibility = null)
 * @method VisibilityInterface getSourceProductVisibility()
 *
 * @ORM\MappedSuperclass
 */
abstract class BaseProductVisibilityResolved
{
    const VISIBILITY_HIDDEN = -1;
    const VISIBILITY_VISIBLE = 1;

    const SOURCE_STATIC = 1;
    const SOURCE_CATEGORY = 2;

    /**
     * @var Website
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

    /**
     * @var Product
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="integer", nullable=true)
     */
    protected $visibility;

    /**
     * @var int
     *
     * @ORM\Column(name="source", type="integer", nullable=true)
     */
    protected $source;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    protected $categoryId;

    /**
     * @param Website $website
     * @param Product $product
     */
    public function __construct(Website $website, Product $product)
    {
        $this->website = $website;
        $this->product = $product;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int|null $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }
}
