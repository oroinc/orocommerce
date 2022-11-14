<?php

namespace Oro\Bundle\CatalogBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the action of creating or editing a category. Allows to assign or remove products form category.
 */
class CategoryHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    protected ObjectManager $manager;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(ObjectManager $manager, EventDispatcherInterface $eventDispatcher)
    {
        $this->manager = $manager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function process($category, FormInterface $form, Request $request)
    {
        $form->setData($category);

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
                $appendProducts = $form->get('appendProducts')->getData();
                $removeProducts = $form->get('removeProducts')->getData();
                $sortOrder = $form->get('sortOrder')->getData()->toArray();
                $this->onSuccess($category, $appendProducts, $removeProducts, $sortOrder);

                $this->eventDispatcher->dispatch(
                    new AfterFormProcessEvent($form, $category),
                    'oro_catalog.category.edit'
                );

                return true;
            }
        }

        return false;
    }

    /**
     * @param Category $category
     * @param Product[] $appendProducts
     * @param Product[] $removeProducts
     * @param Product[] $sortOrder
     */
    protected function onSuccess(
        Category $category,
        array $appendProducts,
        array $removeProducts,
        array $sortOrder
    ): void {
        $this->appendProducts($category, $appendProducts);
        $this->removeProducts($category, $removeProducts);
        $this->sortProducts($category, $appendProducts, $removeProducts, $sortOrder);
        $this->cleanProducts($appendProducts, $removeProducts, $sortOrder);

        $category->getDefaultProductOptions()?->updateUnitPrecision();
        $category->preUpdate();
        $this->manager->persist($category);
        $this->manager->flush();
    }

    /**
     * @param Category $category
     * @param Product[] $products
     */
    protected function appendProducts(Category $category, array $products): void
    {
        $categoryRepository = $this->manager->getRepository(Category::class);
        foreach ($products as $product) {
            $productCategory = $categoryRepository->findOneByProduct($product);

            if ($productCategory instanceof Category) {
                if ($productCategory->getId() === $category->getId()) {
                    continue;
                }

                $productCategory->removeProduct($product);
            }

            $category->addProduct($product);

            if ($productCategory instanceof Category) {
                $categoriesToUpdate = [$productCategory];
                if ($category->getId() !== null) {
                    $categoriesToUpdate[] = $category;
                }
                // both categories must be updated in the same flush
                //EDIT: we will flush $category only if it is an existing one, not a category that is now added
                $this->manager->flush($categoriesToUpdate);
            }
        }
    }

    /**
     * @param Category $category
     * @param Product[] $products
     */
    protected function removeProducts(Category $category, array $products): void
    {
        foreach ($products as $product) {
            $category->removeProduct($product);
        }
    }

    /**
     * @param Category $category
     * @param array $appendProducts
     * @param array $removeProducts
     * @param array $sortOrder
     * @return void
     */
    protected function sortProducts(
        Category $category,
        array $appendProducts,
        array $removeProducts,
        array $sortOrder
    ): void {
        $productRepository = $this->manager->getRepository(Product::class);
        $products = $productRepository->findBy(['id' => array_keys($sortOrder)]);
        foreach ($products as $product) {
            $sortDataInputValue = $sortOrder[$product->getId()]['data']['categorySortOrder'];
            /**
             * We need to :
             *   - Check that the field is in the newly selected fields or already in collection
             *   - Check that the field is not in the removed products
             *   - Compare the old value and the new value
            */
            if (($category->getProducts()->contains($product) || in_array($product, $appendProducts))
                && !in_array($product, $removeProducts)
                && $sortDataInputValue !== $product->getCategorySortOrder()
            ) {
                $product->setCategorySortOrder(is_null($sortDataInputValue) ? null : (float)$sortDataInputValue);
                $this->manager->persist($product);
            }
        }
    }

    /**
     * @param array $appendProducts
     * @param array $removeProducts
     * @param array $sortOrder
     * @return void
     */
    protected function cleanProducts(
        array $appendProducts,
        array $removeProducts,
        array $sortOrder
    ): void {
        /**
         * We need to reset the sorting value of all appended products that have no sorting specified.
         * Their sort value must go back to default in case they were previously associated to another category
         * This behaviour is coherent to the datagrid field loading data only if the right category is selected
         * => see categorySortOrder selection in Oro/Bundle/CatalogBundle/Resources/config/oro/datagrids.yml
         */
        foreach ($appendProducts as $product) {
            if (!array_key_exists($product->getId(), $sortOrder) && !is_null($product->getCategorySortOrder())) {
                $product->setCategorySortOrder(null);
                $this->manager->persist($product);
            }
        }

        /**
         * We need to reset the sorting value of all removed products.
         * Their sort value must go back to default in case they are added to another category after
         */
        foreach ($removeProducts as $product) {
            $product->setCategorySortOrder(null);
            $this->manager->persist($product);
        }
    }
}
