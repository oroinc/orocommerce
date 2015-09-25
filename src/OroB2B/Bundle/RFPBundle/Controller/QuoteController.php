<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;

class QuoteController extends Controller
{
    /**
     * @Route("/create/{id}", name="orob2b_rfp_quote_create", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_sale_quote_create")
     *
     * @param RFPRequest $rfpRequest
     *
     * @return RedirectResponse
     */
    public function createAction(RFPRequest $rfpRequest)
    {
        $this->saveToStorage($rfpRequest);

        return $this->redirectToRoute('orob2b_sale_quote_create', [ProductDataStorage::STORAGE_KEY => true]);
    }

    /**
     * @param RFPRequest $rfpRequest
     */
    protected function saveToStorage(RFPRequest $rfpRequest)
    {
        /** @var ProductDataStorage $storage */
        $storage = $this->get('orob2b_product.service.product_data_storage');

        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'accountUser' => $rfpRequest->getAccountUser()->getId(),
                'account' => $rfpRequest->getAccount()->getId(),
            ],
        ];

        foreach ($rfpRequest->getRequestProducts() as $requestProduct) {
            foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
                $productUnitCode = $requestProductItem->getProductUnit()
                    ? $requestProductItem->getProductUnit()->getCode()
                    : null
                ;
                $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                    ProductDataStorage::PRODUCT_SKU_KEY => $requestProduct->getProduct()->getSku(),
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $requestProductItem->getQuantity(),
                    'commentAccount' => $requestProduct->getComment(),
                    'productUnit' => $productUnitCode,
                    'productUnitCode' => $productUnitCode,
                    'type' => QuoteProduct::TYPE_REQUESTED,
                    'price' => $requestProductItem->getPrice(),
                    'requestProductItem' => $requestProductItem,
                    'requestProduct' => $requestProduct,
                    'allowIncrements' => true,
                ];
            }
        }

        $storage->set($data);
    }
}
