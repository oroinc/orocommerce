<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryPostSubmitListener extends AbstractCategoryListener
{
    /** @var EntityManager */
    protected $categoryManager;

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        if (!$event->getForm()->isValid()) {
            return;
        }
        $category = $event->getData();

        if (!$category || !$category instanceof Category || !$category->getId()) {
            return;
        }

        $form = $event->getForm();

        /** @var string $visibilityCode */
        $visibilityCode = $form->get('categoryVisibility')->getData();
        /** @var ArrayCollection $accountChangeSet */
        $accountChangeSet = $form->get('visibilityForAccount')->getData();
        /** @var ArrayCollection $accountGroupChangeSet */
        $accountGroupChangeSet = $form->get('visibilityForAccountGroup')->getData();

        if ($visibilityCode) {
            $this->processCategoryVisibility($category, $visibilityCode);
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
     * @param string $visibilityCode
     */
    protected function processCategoryVisibility(Category $category, $visibilityCode)
    {
        $categoryVisibility = $this->getEntityRepository($this->categoryVisibilityClass)
            ->findOneBy(['category' => $category]);

        if (!$categoryVisibility) {
            /** @var CategoryVisibility $categoryVisibility */
            $categoryVisibility = new $this->categoryVisibilityClass();
            $categoryVisibility->setCategory($category);
        }

        $this->applyVisibility($category, $categoryVisibility, $visibilityCode);
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
                $item['data']['visibility']
            );
        }
    }

    /**
     * @param Category $category
     * @param VisibilityInterface $visibilityEntity
     * @param string $visibilityCode
     */
    protected function applyVisibility(
        Category $category,
        VisibilityInterface $visibilityEntity,
        $visibilityCode
    ) {
        $em = $this->getCategoryManager($category);
        if ($visibilityCode === $visibilityEntity->getDefault()) {
            if ($visibilityEntity->getVisibility()) {
                $em->remove($visibilityEntity);
            }

            return;
        }

        $visibilityEntity->setVisibility($visibilityCode);

        $em->persist($visibilityEntity);
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountChangeSet
     *
     * @return ArrayCollection|AccountCategoryVisibility[]
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
     * @return ArrayCollection|AccountCategoryVisibility[]
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

    /**
     * @param Category $category
     * @return EntityManager
     */
    protected function getCategoryManager(Category $category)
    {
        if (!$this->categoryManager) {
            $this->categoryManager = $this->getEntityManager($category);
        }

        return $this->categoryManager;
    }
}
