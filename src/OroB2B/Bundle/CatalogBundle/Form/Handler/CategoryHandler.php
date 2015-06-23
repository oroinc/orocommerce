<?php

namespace OroB2B\Bundle\CatalogBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Entity\ProductCategory;
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

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param Category $category
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(Category $category)
    {
        $this->form->setData($category);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $appendProducts = $this->form->get('appendProducts')->getData();
                $removeProducts = $this->form->get('removeProducts')->getData();
                $this->onSuccess($category, $appendProducts, $removeProducts);

                return true;
            }
        }

        return false;
    }

    /**
     * @param Category  $category
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
     * @param Category  $category
     * @param Product[] $products
     */
    protected function appendProducts(Category $category, array $products)
    {
        /** @var $product Product */
        foreach ($products as $product) {
            $productCategory = $this->getProductCategory($product);
            if (!($productCategory instanceof ProductCategory)) {
                $productCategory = new ProductCategory();
            }

            $productCategory->setCategory($category)
                ->setProduct($product);
            $this->manager->persist($productCategory);

            $category->addProduct($productCategory);
        }
    }

    /**
     * @param Category  $category
     * @param Product[] $products
     */
    protected function removeProducts(Category $category, array $products)
    {
        /** @var $product Product */
        foreach ($products as $product) {
            $category->removeProduct($this->getProductCategory($product));
        }
    }

    /**
     * @param Product $product
     *
     * @return ProductCategory
     */
    protected function getProductCategory(Product $product)
    {
        return $this->manager->getRepository('OroB2BCatalogBundle:ProductCategory')->findOneByProduct($product);
    }
}
