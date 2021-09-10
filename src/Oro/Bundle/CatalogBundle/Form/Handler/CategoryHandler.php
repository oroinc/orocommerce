<?php

namespace Oro\Bundle\CatalogBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the action of creating or editing a category. Allows to assign or remove products form category.
 */
class CategoryHandler
{
    use RequestHandlerTrait;

    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $manager;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Category $category
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(Category $category)
    {
        $this->form->setData($category);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->submitPostPutRequest($this->form, $this->request);
            if ($this->form->isValid()) {
                $appendProducts = $this->form->get('appendProducts')->getData();
                $removeProducts = $this->form->get('removeProducts')->getData();
                $this->onSuccess($category, $appendProducts, $removeProducts);

                $this->eventDispatcher->dispatch(
                    new AfterFormProcessEvent($this->form, $category),
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
    protected function onSuccess(Category $category, array $appendProducts, array $removeProducts)
    {
        $this->appendProducts($category, $appendProducts);
        $this->removeProducts($category, $removeProducts);

        if ($category->getDefaultProductOptions()) {
            $category->getDefaultProductOptions()->updateUnitPrecision();
        }
        $category->preUpdate();
        $this->manager->persist($category);
        $this->manager->flush();
    }

    /**
     * @param Category $category
     * @param Product[] $products
     */
    protected function appendProducts(Category $category, array $products)
    {
        $categoryRepository = $this->manager->getRepository('OroCatalogBundle:Category');
        /** @var $product Product */
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
    protected function removeProducts(Category $category, array $products)
    {
        /** @var $product Product */
        foreach ($products as $product) {
            $category->removeProduct($product);
        }
    }
}
