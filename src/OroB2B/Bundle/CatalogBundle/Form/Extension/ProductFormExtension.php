<?php

namespace OroB2B\Bundle\CatalogBundle\Form\Extension;

use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class ProductFormExtension extends AbstractTypeExtension
{
    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->entityManager = $registry->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'category',
                CategoryTreeType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.catalog.category.entity_label'
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product || !$product->getId()) {
            return;
        }

        $category = $this->getCategoryRepository()->findOneByProduct($product);

        if ($category instanceof Category) {
            $event->getForm()->get('category')->setData($category);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var Category $category */
        $category = $form->get('category')->getData();
        /** @var Category $productCategory */
        $productCategory = $this->getCategoryRepository()->findOneByProduct($product);

        if (
            $productCategory instanceof Category
            && $category !== $productCategory
        ) {
            $productCategory->removeProduct($product);
            $this->entityManager->persist($productCategory);
        }

        if($category instanceof Category) {
            $category->addProduct($product);
            $this->entityManager->persist($category);
        }
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->entityManager->getRepository('OroB2BCatalogBundle:Category');
    }
}
