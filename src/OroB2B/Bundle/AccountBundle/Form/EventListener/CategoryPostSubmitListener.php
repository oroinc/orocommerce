<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormEvent;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryPostSubmitListener extends AbstractCategoryListener
{
    const CATEGORY_VISIBILITY = 'category_visibility';
    const ACCOUNT_CATEGORY_VISIBILITY = 'acc_ctgry_visibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY = 'acc_grp_ctgry_vsblity';

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /**
     * {@inheritdoc}
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(ManagerRegistry $registry, EnumValueProvider $enumValueProvider)
    {
        parent::__construct($registry);

        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $category = $event->getData();

        if (!$category || !$category instanceof Category || !$category->getId()) {
            return;
        }

        $form = $event->getForm();

        /** @var AbstractEnumValue $visibilityEnum */
        $visibilityEnum = $form->get('categoryVisibility')->getData();
        /** @var ArrayCollection $accountChangeSet */
        $accountChangeSet = $form->get('visibilityForAccount')->getData();
        /** @var ArrayCollection $accountGroupChangeSet */
        $accountGroupChangeSet = $form->get('visibilityForAccountGroup')->getData();

        if ($visibilityEnum) {
            $this->processCategoryVisibility($category, $visibilityEnum);
        }

        if (!$accountChangeSet->isEmpty()) {
            $this->processAccountVisibility($category, $accountChangeSet);
        }

        if (!$accountGroupChangeSet->isEmpty()) {
            $this->processAccountGroupVisibility($category, $accountGroupChangeSet);
        }

        $this->getEntityManager($category)->flush();
    }

    /**
     * @param Category $category
     * @param AbstractEnumValue $visibilityEnum
     */
    protected function processCategoryVisibility(Category $category, AbstractEnumValue $visibilityEnum)
    {
        $categoryVisibility = $this->getEntityRepository($this->categoryVisibilityClass)
            ->findOneBy(['category' => $category]);

        if (!$categoryVisibility) {
            /** @var CategoryVisibility $categoryVisibility */
            $categoryVisibility = new $this->categoryVisibilityClass();
            $categoryVisibility->setCategory($category);
        }

        $this->applyVisibility($category, $categoryVisibility, self::CATEGORY_VISIBILITY, $visibilityEnum->getId());
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountChangeSet
     */
    protected function processAccountVisibility(Category $category, ArrayCollection $accountChangeSet)
    {
        $accountVisibilities = $this->getAccountVisibilities($category, $accountChangeSet);
        foreach ($accountChangeSet as $item) {
            /** @var Account $account */
            $account = $item['entity'];

            $accountCategoryVisibility = $accountVisibilities->offsetGet($account->getId());
            if (!$accountCategoryVisibility) {
                /** @var AccountCategoryVisibility $accountCategoryVisibility */
                $accountCategoryVisibility = new $this->accountCategoryVisibilityClass();
                $accountCategoryVisibility->setCategory($category)->setAccount($account);
            }

            $this->applyVisibility(
                $category,
                $accountCategoryVisibility,
                self::ACCOUNT_CATEGORY_VISIBILITY,
                $item['data']['visibility']
            );
        }
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountChangeSet
     */
    protected function processAccountGroupVisibility(Category $category, ArrayCollection $accountChangeSet)
    {
        $accountGroupVisibilities = $this->getAccountGroupVisibilities($category, $accountChangeSet);
        foreach ($accountChangeSet as $item) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $item['entity'];

            $accountGroupCategoryVisibility = $accountGroupVisibilities->offsetGet($accountGroup->getId());
            if (!$accountGroupCategoryVisibility) {
                /** @var AccountGroupCategoryVisibility $accountGroupCategoryVisibility */
                $accountGroupCategoryVisibility = new $this->accountGroupCategoryVisibilityClass();
                $accountGroupCategoryVisibility->setCategory($category)->setAccountGroup($accountGroup);
            }

            $this->applyVisibility(
                $category,
                $accountGroupCategoryVisibility,
                self::ACCOUNT_GROUP_CATEGORY_VISIBILITY,
                $item['data']['visibility']
            );
        }
    }

    /**
     * @param Category $category
     * @param CategoryVisibility|AccountCategoryVisibility|AccountGroupCategoryVisibility $visibilityEntity
     * @param string $enumCode
     * @param string $visibilityCode
     */
    protected function applyVisibility(Category $category, $visibilityEntity, $enumCode, $visibilityCode)
    {
        $em = $this->getEntityManager($category);
        if ($visibilityCode === $visibilityEntity->getDefault()) {
            if ($visibilityEntity->getVisibility()) {
                $em->remove($visibilityEntity);
            }

            return;
        }

        $visibility = $this->enumValueProvider->getEnumValueByCode($enumCode, $visibilityCode);
        $visibilityEntity->setVisibility($visibility);

        $em->persist($visibilityEntity);
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountChangeSet
     *
     * @return ArrayCollection|\OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility[]
     */
    protected function getAccountVisibilities(Category $category, ArrayCollection $accountChangeSet)
    {
        /** @var Account[] $accounts */
        $accounts = $accountChangeSet
            ->map(
                function ($item) {
                    return $item['entity'];
                }
            )
            ->toArray();

        $visibilities = new ArrayCollection();
        $this->getAccountCategoryVisibilityRepository()
            ->findForAccounts($accounts, $category)->map(
                function ($visibility) use ($visibilities) {
                    /** @var AccountCategoryVisibility $visibility */
                    $visibilities->offsetSet($visibility->getAccount()->getId(), $visibility);
                }
            );

        return $visibilities;
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountGroupChangeSet
     *
     * @return ArrayCollection|\OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility[]
     */
    protected function getAccountGroupVisibilities(Category $category, ArrayCollection $accountGroupChangeSet)
    {
        /** @var AccountGroup[] $accountGroups */
        $accountGroups = $accountGroupChangeSet
            ->map(
                function ($item) {
                    return $item['entity'];
                }
            )
            ->toArray();

        $visibilities = new ArrayCollection();
        $this->getAccountGroupCategoryVisibilityRepository()
            ->findForAccountGroups($accountGroups, $category)
            ->map(
                function ($visibility) use ($visibilities) {
                    /** @var AccountGroupCategoryVisibility $visibility */
                    $visibilities->offsetSet($visibility->getAccountGroup()->getId(), $visibility);
                }
            );

        return $visibilities;
    }
}
