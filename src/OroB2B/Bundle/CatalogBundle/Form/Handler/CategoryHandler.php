<?php

namespace OroB2B\Bundle\CatalogBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Event\CategoryEditEvent;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CategoryHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var  EventDispatcher */
    protected $eventDispatcher;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        EventDispatcher $eventDispatcher
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
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $this->eventDispatcher->dispatch(CategoryEditEvent::NAME, new CategoryEditEvent($this->form));
                $appendProducts = $this->form->get('appendProducts')->getData();
                $removeProducts = $this->form->get('removeProducts')->getData();
                $this->onSuccess($category, $appendProducts, $removeProducts);

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

        $this->manager->persist($category);
        $this->manager->flush();
    }

    /**
     * @param Category $category
     * @param Product[] $products
     */
    protected function appendProducts(Category $category, array $products)
    {
        $categoryRepository = $this->manager->getRepository('OroB2BCatalogBundle:Category');
        /** @var $product Product */
        foreach ($products as $product) {
            $productCategory = $categoryRepository->findOneByProduct($product);

            if ($productCategory instanceof Category) {
                $productCategory->removeProduct($product);
                $this->manager->flush($productCategory);
            }

            $category->addProduct($product);
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
