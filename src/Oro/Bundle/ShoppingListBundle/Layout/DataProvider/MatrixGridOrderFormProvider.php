<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;

/**
 * Provides matrix forms and matrix form views which can be used to update configurable products.
 */
class MatrixGridOrderFormProvider extends AbstractFormProvider
{
    /**
     * @var MatrixGridOrderManager
     */
    private $matrixOrderManager;

    /**
     * @var FormRenderer
     */
    private $twigRenderer;

    /**
     * @param MatrixGridOrderManager $matrixOrderManager
     */
    public function setMatrixOrderManager($matrixOrderManager)
    {
        $this->matrixOrderManager = $matrixOrderManager;
    }

    public function setTwigRenderer(FormRenderer $twigRenderer)
    {
        $this->twigRenderer = $twigRenderer;
    }

    /**
     * @param Product           $product
     * @param ShoppingList|null $shoppingList
     * @return FormInterface
     */
    public function getMatrixOrderForm(Product $product, ShoppingList $shoppingList = null)
    {
        return $this->getForm(
            MatrixCollectionType::class,
            $this->matrixOrderManager->getMatrixCollection($product, $shoppingList)
        );
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param ShoppingList $shoppingList
     * @return FormInterface
     */
    public function getMatrixOrderByUnitForm(Product $product, ProductUnit $productUnit, ShoppingList $shoppingList)
    {
        $collection = $this->matrixOrderManager->getMatrixCollectionForUnit($product, $productUnit, $shoppingList);

        return $this->getForm(MatrixCollectionType::class, $collection);
    }

    /**
     * @param Product|null      $product
     * @param ShoppingList|null $shoppingList
     * @return FormView
     */
    public function getMatrixOrderFormView(Product $product = null, ShoppingList $shoppingList = null)
    {
        if (!$product) {
            return $this->getFormView(MatrixCollectionType::class);
        }

        $collection = $this->matrixOrderManager->getMatrixCollection($product, $shoppingList);

        return $this->getFormView(
            MatrixCollectionType::class,
            $collection,
            [],
            ['cacheKey' => md5(serialize($collection))]
        );
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param ShoppingList $shoppingList
     * @return FormView
     */
    public function getMatrixOrderByUnitFormView(Product $product, ProductUnit $productUnit, ShoppingList $shoppingList)
    {
        $collection = $this->matrixOrderManager->getMatrixCollectionForUnit($product, $productUnit, $shoppingList);

        return $this->getFormView(
            MatrixCollectionType::class,
            $collection,
            [],
            ['cacheKey' => md5(serialize($collection))]
        );
    }

    /**
     * @param Product|null $product
     * @param ShoppingList|null $shoppingList
     * @return string
     */
    public function getMatrixOrderFormHtml(Product $product = null, ShoppingList $shoppingList = null)
    {
        $formView = $this->getMatrixOrderFormView($product, $shoppingList);

        return $this->twigRenderer->searchAndRenderBlock($formView, 'widget');
    }
}
