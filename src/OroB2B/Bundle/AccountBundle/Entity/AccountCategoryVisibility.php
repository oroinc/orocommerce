<?php

namespace OroB2B\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\AccountBundle\Model\ExtendAccountCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository")
 * @ORM\Table(name="orob2b_acc_category_visibility")
 * @Config
 */
class AccountCategoryVisibility extends ExtendAccountCategoryVisibility implements DefaultVisibilityInterface
{
    const PARENT_CATEGORY = 'parent_category';
    const CONFIG = 'config';
    const ACCOUNT_GROUP = 'account_group';
    const VISIBLE = 'visible';
    const HIDDEN = 'hidden';

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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CatalogBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $category;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

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
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return self::PARENT_CATEGORY;
    }
}
