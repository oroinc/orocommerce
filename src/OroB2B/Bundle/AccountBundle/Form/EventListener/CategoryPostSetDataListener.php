<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;

class CategoryPostSetDataListener extends AbstractCategoryListener
{
    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Category|null $category */
        $category = $event->getData();

        if (!$category || !$category instanceof Category || !$category->getId()) {
            return;
        }

        $form = $event->getForm();

        $this->setCategoryVisibility($form, $category);
        $this->setAccountCategoryVisibility($form, $category);
        $this->setAccountGroupCategoryVisibility($form, $category);
    }

    /**
     * @param FormInterface $form
     * @param Category $category
     */
    protected function setCategoryVisibility(FormInterface $form, Category $category)
    {
        $categoryVisibility = $this->getEntityRepository($this->categoryVisibilityClass)
            ->findOneBy(['category' => $category]);

        if ($categoryVisibility instanceof CategoryVisibility) {
            $form->get('categoryVisibility')->setData($categoryVisibility->getVisibility());
        }
    }

    /**
     * @param FormInterface $form
     * @param Category $category
     */
    protected function setAccountCategoryVisibility(FormInterface $form, Category $category)
    {
        $accountCategoryVisibilities = $this->getAccountCategoryVisibilityRepository()
            ->findBy(['category' => $category]);

        $accountCategoryVisibilityData = [];
        /** @var AccountCategoryVisibility $accountCategoryVisibility */
        foreach ($accountCategoryVisibilities as $accountCategoryVisibility) {
            $accountCategoryVisibilityData[$accountCategoryVisibility->getAccount()->getId()] = [
                'entity' => $accountCategoryVisibility->getAccount(),
                'data' => [
                    'visibility' => $accountCategoryVisibility->getVisibility()->getId(),
                ],
            ];
        }

        if (count($accountCategoryVisibilityData) > 0) {
            $form->get('visibilityForAccount')->setData($accountCategoryVisibilityData);
        }
    }

    /**
     * @param FormInterface $form
     * @param Category $category
     */
    protected function setAccountGroupCategoryVisibility(FormInterface $form, Category $category)
    {
        $accountGroupCategoryVisibilities = $this->getAccountGroupCategoryVisibilityRepository()
            ->findBy(['category' => $category]);

        $accountGroupCategoryVisibilityData = [];
        /** @var AccountGroupCategoryVisibility $accountGroupCategoryVisibility */
        foreach ($accountGroupCategoryVisibilities as $accountGroupCategoryVisibility) {
            $accountGroupCategoryVisibilityData[$accountGroupCategoryVisibility->getAccountGroup()->getId()] = [
                'entity' => $accountGroupCategoryVisibility->getAccountGroup(),
                'data' => [
                    'visibility' => $accountGroupCategoryVisibility->getVisibility()->getId(),
                ],
            ];
        }

        if (count($accountGroupCategoryVisibilityData) > 0) {
            $form->get('visibilityForAccountGroup')->setData($accountGroupCategoryVisibilityData);
        }
    }
}
