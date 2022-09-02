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
                $this->onSuccess($category, $appendProducts, $removeProducts);

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
     */
    protected function onSuccess(Category $category, array $appendProducts, array $removeProducts): void
    {
        $this->appendProducts($category, $appendProducts);
        $this->removeProducts($category, $removeProducts);

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
}
