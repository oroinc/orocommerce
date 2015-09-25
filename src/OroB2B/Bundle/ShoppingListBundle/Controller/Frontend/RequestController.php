<?php

namespace OroB2B\Bundle\ShoppingListBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class RequestController extends Controller
{
    /**
     * @Route("/create/{id}", name="orob2b_shoppinglist_frontend_request_create", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_rfp_frontend_request_create")
     *
     * @param ShoppingList $shoppingList
     *
     * @return RedirectResponse
     */
    public function createAction(ShoppingList $shoppingList)
    {
        $this->saveToStorage($shoppingList);
        return $this->redirectToRoute('orob2b_rfp_frontend_request_create', [ProductDataStorage::STORAGE_KEY => true]);
    }

    /**
     * @param ShoppingList $shoppingList
     */
    protected function saveToStorage(ShoppingList $shoppingList)
    {
        /** @var ProductDataStorage $storage */
        $storage = $this->get('orob2b_product.service.product_data_storage');

        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'accountUser' => $shoppingList->getAccountUser()->getId(),
                'account' => $shoppingList->getAccount()->getId(),
            ],
        ];

        foreach ($shoppingList->getLineItems() as $lineItem) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                ProductDataStorage::PRODUCT_QUANTITY_KEY => $lineItem->getQuantity(),
                'comment' => $lineItem->getNotes(),
                'productUnit' => $lineItem->getUnit()->getCode(),
                'productUnitCode' => $lineItem->getUnit()->getCode(),
            ];
        }

        $storage->set($data);
    }
}
