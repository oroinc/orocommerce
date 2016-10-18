<?php

namespace Oro\Bundle\CustomerBundle\Entity\Visibility;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAwareInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *      name="oro_acc_category_visibility",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_acc_ctgr_vis_uidx",
 *              columns={"category_id", "account_id"}
 *          )
 *      }
 * )
 * @Config
 */
class AccountCategoryVisibility implements VisibilityInterface, AccountAwareInterface
{
    const PARENT_CATEGORY = 'parent_category';
    const CATEGORY = 'category';
    const ACCOUNT_GROUP = 'account_group';

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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CatalogBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $category;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=255, nullable=true)
     */
    protected $visibility;

    /**
     * @return int
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
     *
     * @return $this
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @param Category $category
     * @return string
     */
    public static function getDefault($category)
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
     * @param Category $category
     * @return array
     */
    public static function getVisibilityList($category)
    {
        $visibilityList = [
            self::ACCOUNT_GROUP,
            self::CATEGORY,
            self::PARENT_CATEGORY,
            self::HIDDEN,
            self::VISIBLE,
        ];
        if ($category instanceof Category && !$category->getParentCategory()) {
            unset($visibilityList[array_search(self::PARENT_CATEGORY, $visibilityList)]);
        }
        return $visibilityList;
    }

    /**
     * @return Category
     */
    public function getTargetEntity()
    {
        return $this->getCategory();
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function setTargetEntity($category)
    {
        return $this->setCategory($category);
    }
}
