<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * @ORM\Entity(
 *      repositoryClass="Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository"
 * )
 * @ORM\Table(
 *      name="oro_acc_product_visibility",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_acc_prod_vis_uidx",
 *              columns={"scope_id", "product_id"}
 *          )
 *      }
 * )
 * @Config
 */
class AccountProductVisibility implements VisibilityInterface
{
    const ACCOUNT_GROUP = 'account_group';
    const CURRENT_PRODUCT = 'current_product';
    const CATEGORY = 'category';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=255, nullable=true)
     */
    protected $visibility;

    /**
     * @var Scope
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ScopeBundle\Entity\Scope")
     * @ORM\JoinColumn(name="scope_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $scope;

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @param Product $product
     * @return string
     */
    public static function getDefault($product)
    {
        return self::ACCOUNT_GROUP;
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param Product $product
     * @return array
     */
    public static function getVisibilityList($product)
    {
        return [
            self::ACCOUNT_GROUP,
            self::CURRENT_PRODUCT,
            self::CATEGORY,
            self::HIDDEN,
            self::VISIBLE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntity()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setTargetEntity($product)
    {
        $this->setProduct($product);

        return $this;
    }

    /**
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function setScope(Scope $scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
