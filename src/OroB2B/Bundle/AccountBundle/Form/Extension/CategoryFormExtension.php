<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Genemu\Bundle\FormBundle\Form\JQuery\DataTransformer\ArrayToStringTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

class CategoryFormExtension extends AbstractTypeExtension
{
    /** @var EntityRepository */
    protected $categoryVisibilityRepository;
    /** @var EntityRepository */
    protected $accountCategoryVisibilityRepository;
    /** @var EntityRepository */
    protected $accountGroupCategoryVisibilityRepository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->categoryVisibilityRepository = $registry
            ->getManagerForClass('OroB2BAccountBundle:CategoryVisibility')
            ->getRepository('OroB2BAccountBundle:CategoryVisibility');
        $this->accountCategoryVisibilityRepository = $registry
            ->getManagerForClass('OroB2BAccountBundle:AccountCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:AccountCategoryVisibility');
        $this->accountGroupCategoryVisibilityRepository = $registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:AccountGroupCategoryVisibility');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'categoryVisibility',
                'oro_enum_select',
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.account.categoryvisibility.entity_label',
                    'enum_code' => 'category_visibility',
                    'configs' => [
                        'allowClear' => false,
                        'placeholder' => 'orob2b.account.categoryvisibility.default.label'
                    ]
                ]
            )
            ->add(
                'visibilityForAccount',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\Account'
                ]
            )
            ->add(
                'visibilityForAccountGroup',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup'
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Category|null $category */
        $category = $event->getData();

        if (!$category || !$category->getId()) {
            return;
        }

        $this->setCategoryVisibility($event, $category);
        $this->setAccountCategoryVisibility($event, $category);
        $this->setAccountGroupCategoryVisibility($event, $category);
    }

    /**
     * @param FormEvent $event
     * @param Category  $category
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
     * @param Category  $category
     */
    protected function setAccountCategoryVisibility(FormEvent $event, Category $category)
    {
        $accountCategoryVisibilities = $this->accountCategoryVisibilityRepository->findBy(['category' => $category]);

        $accountCategoryVisibilityData = [];
        /** @var AccountCategoryVisibility $accountCategoryVisibility */
        foreach ($accountCategoryVisibilities as $accountCategoryVisibility) {
            $accountCategoryVisibilityData[$accountCategoryVisibility->getAccount()->getId()] = [
                'entity' => $accountCategoryVisibility->getAccount(),
                'data'   => [
                    'visibility' => $accountCategoryVisibility->getVisibility()->getId()
                ]
            ];
        }

        if (count($accountCategoryVisibilityData) > 0) {
            $event->getForm()->get('visibilityForAccount')->setData($accountCategoryVisibilityData);
        }
    }

    /**
     * @param FormEvent $event
     * @param Category  $category
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
                'data'   => [
                    'visibility' => $accountGroupCategoryVisibility->getVisibility()->getId()
                ]
            ];
        }

        if (count($accountGroupCategoryVisibilityData) > 0) {
            $event->getForm()->get('visibilityForAccountGroup')->setData($accountGroupCategoryVisibilityData);
        }
    }
}
