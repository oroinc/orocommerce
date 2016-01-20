<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeAccountSubtreeCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeAccountSubtreeCacheBuilder */
    protected $visibilityChangeAccountSubtreeCacheBuilder;

    /**
     * @param VisibilityChangeAccountSubtreeCacheBuilder $visibilityChangeAccountSubtreeCacheBuilder
     */
    public function setVisibilityChangeAccountSubtreeCacheBuilder(
        VisibilityChangeAccountSubtreeCacheBuilder $visibilityChangeAccountSubtreeCacheBuilder
    ) {
        $this->visibilityChangeAccountSubtreeCacheBuilder = $visibilityChangeAccountSubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|AccountCategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();
        $account = $visibilitySettings->getAccount();

        $selectedVisibility = $visibilitySettings->getVisibility();

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['account' => $account, 'category' => $category];

        $repository = $this->getRepository();

        $hasAccountCategoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        if (!$hasAccountCategoryVisibilityResolved
            && $selectedVisibility !== AccountCategoryVisibility::ACCOUNT_GROUP
        ) {
            $insert = true;
        }

        if (in_array($selectedVisibility, [AccountCategoryVisibility::HIDDEN, AccountCategoryVisibility::VISIBLE])) {
            $visibility = $this->convertStaticCategoryVisibility($selectedVisibility);
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility == AccountCategoryVisibility::CATEGORY) {
            $visibility = $this->registry
                ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
                ->getFallbackToAllVisibility($category, $this->getCategoryVisibilityConfigValue());
            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountCategoryVisibilityResolved::SOURCE_STATIC,
            ];
        } elseif ($selectedVisibility == AccountCategoryVisibility::ACCOUNT_GROUP) {
            if ($account->getGroup()) {
                // Fallback to account group is default for account and should be removed if exist
                if ($hasAccountCategoryVisibilityResolved) {
                    $delete = true;
                }

                $visibility = $this->registry
                    ->getManagerForClass(
                        'OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved'
                    )
                    ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
                    ->getFallbackToGroupVisibility(
                        $category,
                        $account->getGroup(),
                        $this->getCategoryVisibilityConfigValue()
                    );
            } else {
                throw new \LogicException('Impossible set account group visibility to account without account group');
            }
        } elseif ($selectedVisibility == AccountCategoryVisibility::PARENT_CATEGORY) {
            $parentCategory = $category->getParentCategory();
            $visibility = $this->getRepository()->getCategoryVisibility(
                $parentCategory,
                $account,
                $this->getCategoryVisibilityConfigValue()
            );

            $update = [
                'visibility' => $visibility,
                'sourceCategoryVisibility' => $visibilitySettings,
                'source' => AccountCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ];
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown visibility %s', $selectedVisibility));
        }

        $this->executeDbQuery($repository, $insert, $delete, $update, $where);

        $this->visibilityChangeAccountSubtreeCacheBuilder->resolveVisibilitySettings(
            $category,
            $account,
            $visibility
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof AccountCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        // TODO: Implement in scope of BB-1650
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @return AccountCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @param Category $category
     * @param Account $account
     * @return null|AccountCategoryVisibilityResolved
     */
    protected function getCategoryVisibilityResolved(Category $category, Account $account)
    {
        return $this->getRepository()->findByPrimaryKey($category, $account);
    }

    /**
     * @param string $selectedVisibility
     * @return int
     */
    protected function convertStaticCategoryVisibility($selectedVisibility)
    {
        return ($selectedVisibility == CategoryVisibility::VISIBLE)
            ? BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            : BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @return int
     */
    protected function getCategoryVisibilityConfigValue()
    {
        return ($this->configManager->get('oro_b2b_account.category_visibility') == CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }
}
