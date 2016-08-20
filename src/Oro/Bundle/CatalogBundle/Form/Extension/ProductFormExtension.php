<?php

namespace Oro\Bundle\CatalogBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

class ProductFormExtension extends AbstractTypeExtension
{
    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
                    'label' => 'oro.catalog.category.entity_label'
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * @param FormEvent $event
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
     * @param FormEvent $event
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

        if (null !== $product->getId()) {
            /** @var Category $productCategory */
            $productCategory = $this->getCategoryRepository()->findOneByProduct($product);

            if ($productCategory instanceof Category && $category !== $productCategory) {
                $productCategory->removeProduct($product);
            }
        }

        if ($category instanceof Category) {
            $category->addProduct($product);
        }
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        if (!$this->categoryRepository) {
            $this->categoryRepository = $this->registry->getManagerForClass('OroCatalogBundle:Category')
                ->getRepository('OroCatalogBundle:Category');
        }

        return $this->categoryRepository;
    }
}
