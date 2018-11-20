<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Adds event listener for Product entity form to handle "category" field.
 */
class AddUpdateCategoryForProductFormListener implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $formBuilder = $context->getFormBuilder();
        if (!$formBuilder) {
            // the form builder does not exist
            return;
        }

        if ($formBuilder->has('category')) {
            $formBuilder->get('category')->addEventListener(
                FormEvents::PRE_SUBMIT,
                [$this, 'onPreSubmit']
            );
            $formBuilder->get('category')->addEventListener(
                FormEvents::POST_SUBMIT,
                [$this, 'onPostSubmit']
            );
        }
    }

    /**
     * It's a workaround for doctrine2 bug
     * @see https://github.com/doctrine/doctrine2/issues/6186
     * remove this in https://magecore.atlassian.net/browse/BB-11411
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $submittedData = $event->getData();
        if (!empty($submittedData)) {
            /** @var Product $product */
            $product = $event->getForm()->getParent()->getData();
            $this->getExistingCategory($product);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Product $product */
        $product = $form->getParent()->getData();

        $oldCategory = $this->getExistingCategory($product);
        $newCategory = $this->getNewCategory($form);

        // use comparison of the old and new category objects to avoid call
        // of removeProduct/addProduct methods if the product category is not changed
        if ($newCategory !== $oldCategory) {
            if (null !== $oldCategory) {
                $oldCategory->removeProduct($product);
            }
            if (null !== $newCategory) {
                $newCategory->addProduct($product);
            }
        }
    }

    /**
     * Gets the category that contains the given product.
     *
     * @param Product $product
     *
     * @return Category|null
     */
    private function getExistingCategory(Product $product)
    {
        if (!$product->getId()) {
            return null;
        }

        /** @var CategoryRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(Category::class);

        return $repo->findOneByProduct($product);
    }

    /**
     * Gets the category that is submitted via API request.
     *
     * @param FormInterface $categoryForm
     *
     * @return Category|null
     */
    private function getNewCategory(FormInterface $categoryForm)
    {
        $data = $categoryForm->getData();

        // use instanceof here instead of just comparison with NULL
        // because in case if submitted data is invalid it was not converted to an object and keeps an array
        if ($data instanceof Category) {
            return $data;
        }

        return null;
    }
}
