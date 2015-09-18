<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository;

use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;

class CategoryPostSetDataListener
{
    /** @var EntityRepository */
    protected $categoryVisibilityRepository;

    /** @var AccountCategoryVisibilityRepository */
    protected $accountCategoryVisibilityRepository;

    /** @var AccountGroupCategoryVisibilityRepository */
    protected $accountGroupCategoryVisibilityRepository;

    /** @var Registry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

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

        $this->categoryVisibilityRepository = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:CategoryVisibility')
            ->getRepository('OroB2BAccountBundle:CategoryVisibility');
        $this->accountCategoryVisibilityRepository = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:AccountCategoryVisibility');
        $this->accountGroupCategoryVisibilityRepository = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:AccountGroupCategoryVisibility');

        $this->setCategoryVisibility($event, $category);
        $this->setAccountCategoryVisibility($event, $category);
        $this->setAccountGroupCategoryVisibility($event, $category);
    }

    /**
     * @param FormEvent $event
     * @param Category $category
     */
    protected function setCategoryVisibility(FormEvent $event, Category $category)
    {
        $categoryVisibility = $this->categoryVisibilityRepository->findOneBy(['category' => $category]);

        if ($categoryVisibility instanceof CategoryVisibility) {
            $event->getForm()->get('categoryVisibility')->setData($categoryVisibility->getVisibility());
        }
    }

    /**
     * @param FormEvent $event
     * @param Category $category
     */
    protected function setAccountCategoryVisibility(FormEvent $event, Category $category)
    {
        $accountCategoryVisibilities = $this->accountCategoryVisibilityRepository->findBy(['category' => $category]);

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
            $event->getForm()->get('visibilityForAccount')->setData($accountCategoryVisibilityData);
        }
    }

    /**
     * @param FormEvent $event
     * @param Category $category
     */
    protected function setAccountGroupCategoryVisibility(FormEvent $event, Category $category)
    {
        $accountGroupCategoryVisibilities = $this->accountGroupCategoryVisibilityRepository
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
            $event->getForm()->get('visibilityForAccountGroup')->setData($accountGroupCategoryVisibilityData);
        }
    }
}
