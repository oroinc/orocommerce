<?php

namespace Oro\Bundle\CatalogBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds field "category" to product form and handles changes in it.
 */
class ProductFormExtension extends AbstractTypeExtension
{
    protected ManagerRegistry $registry;
    protected AuthorizationCheckerInterface $authorizationChecker;
    private ?CategoryRepository $categoryRepository = null;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->registry = $registry;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->authorizationChecker->isGranted('oro_catalog_category_view')) {
            return;
        }

        $builder
            ->add(
                'category',
                CategoryTreeType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.catalog.category.entity_label'
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    public function onPostSetData(FormEvent $event): void
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

    public function onPostSubmit(FormEvent $event): void
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

    protected function getCategoryRepository(): CategoryRepository
    {
        if (!$this->categoryRepository) {
            $this->categoryRepository = $this->registry->getRepository(Category::class);
        }

        return $this->categoryRepository;
    }
}
