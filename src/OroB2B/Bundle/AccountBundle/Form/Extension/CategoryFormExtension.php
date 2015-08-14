<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Repository\CategoryVisibilityRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

class CategoryFormExtension extends AbstractTypeExtension
{
    /**
     * @var CategoryVisibilityRepository
     */
    protected $categoryVisibilityRepository;

    /**
     * @param ManagerRegistry   $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->categoryVisibilityRepository = $registry->getManagerForClass('OroB2BAccountBundle:CategoryVisibility')
            ->getRepository('OroB2BAccountBundle:CategoryVisibility');
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
                    'configs'   => [
                        'allowClear' => false,
                    ]
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

        $categoryVisibility = $this->categoryVisibilityRepository->findOneByCategory($category);

        if ($categoryVisibility instanceof CategoryVisibility) {
            $event->getForm()->get('categoryVisibility')->setData($categoryVisibility);
        }
    }
}
