<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;

class MatrixGridOrderFormProvider extends AbstractFormProvider
{
    const MATRIX_GRID_ORDER_ROUTE_NAME = 'oro_shopping_list_frontend_matrix_grid_order';

    /**
     * @var MatrixGridOrderManager
     */
    private $matrixOrderManager;

    /**
     * @var TwigRenderer
     */
    private $twigRenderer;

    /**
     * @param MatrixGridOrderManager $matrixOrderManager
     */
    public function setMatrixOrderManager($matrixOrderManager)
    {
        $this->matrixOrderManager = $matrixOrderManager;
    }

    /**
     * @param TwigRenderer $twigRenderer
     */
    public function setTwigRenderer(TwigRenderer $twigRenderer)
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
        $collection = $this->matrixOrderManager->getMatrixCollection($product, $shoppingList);

        return $this->getForm(MatrixCollectionType::class, $collection);
    }

    /**
     * @param Product           $product
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
     * @param ShoppingList|null $shoppingList
     * @return string
     */
    public function getMatrixOrderFormHtml(Product $product = null, ShoppingList $shoppingList = null)
    {
        $formView = $this->getMatrixOrderFormView($product, $shoppingList);

        return $this->twigRenderer->searchAndRenderBlock($formView, 'widget');
    }
}
