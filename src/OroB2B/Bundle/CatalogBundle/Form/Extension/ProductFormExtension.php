<?php

namespace OroB2B\Bundle\CatalogBundle\Form\Extension;

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
use OroB2B\Bundle\CatalogBundle\Entity\ProductCategory;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\ProductCategoryRepository;

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
        $this->entityManager = $registry->getManagerForClass('OroB2BCatalogBundle:ProductCategory');
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
                    'mapped'   => false,
                    'label'    => 'orob2b.catalog.category.entity_label'
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

        $productCategory = $this->getProductCategoryRepository()->findOneByProduct($product);

        if ($productCategory instanceof ProductCategory) {
            $event->getForm()->get('category')->setData($productCategory->getCategory());
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

        $category        = $form->get('category')->getData();
        $productCategory = $this->getProductCategoryRepository()->findOneByProduct($product);

        if (!($category instanceof Category)) {
            $this->removeProductCategory($productCategory);

            return;
        }

        $this->persistProductCategory($productCategory, $category, $product);
    }

    /**
     * @param ProductCategory|null $productCategory
     * @param Category             $category
     * @param Product              $product
     */
    protected function persistProductCategory($productCategory, Category $category, Product $product)
    {
        if (!($productCategory instanceof ProductCategory)) {
            $productCategory = new ProductCategory();
        }

        $productCategory->setCategory($category)
            ->setProduct($product);

        $this->entityManager->persist($productCategory);
    }

    /**
     * @param ProductCategory|null $productCategory
     */
    protected function removeProductCategory($productCategory)
    {
        if ($productCategory instanceof ProductCategory) {
            $this->entityManager->remove($productCategory);
        }
    }

    /**
     * @return ProductCategoryRepository
     */
    protected function getProductCategoryRepository()
    {
        return $this->entityManager->getRepository('OroB2BCatalogBundle:ProductCategory');
    }
}
