<?php

namespace OroB2B\Bundle\RFPBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class RequestManager
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(SecurityFacade $securityFacade, DoctrineHelper $doctrineHelper)
    {
        $this->securityFacade = $securityFacade;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return Request
     */
    public function create()
    {
        $request = new Request();
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof AccountUser) {
            $request
                ->setAccountUser($user)
                ->setAccount($user->getAccount())
                ->setFirstName($user->getFirstName())
                ->setLastName($user->getLastName())
                ->setCompany($user->getAccount()->getName())
                ->setEmail($user->getEmail());
        }

        return $request;
    }

    /**
     * @param Request $request
     * @param array $productLineItems
     */
    public function addProductLineItemsToRequest(Request $request, array $productLineItems)
    {
        $units = [];
        foreach ($productLineItems as $productId => $items) {
            $filteredItems = [];
            foreach ($items as $item) {
                $units[$item['unit']] = true;
                $filteredItems[$item['unit']] = $item['quantity'];
            }
            $productLineItems[$productId] = $filteredItems;
        }
        $productIds = array_keys($productLineItems);
        $products = $this->getProducts($productIds);
        $units = $this->getUnits($productIds, array_keys($units));
        foreach ($productLineItems as $productId => $requestProductItems) {
            if (!array_key_exists($productId, $products)) {
                continue;
            }
            $requestProduct = new RequestProduct();
            $requestProduct->setProduct($products[$productId]);

            foreach ($requestProductItems as $unit => $quantity) {
                if (!array_key_exists($unit, $units)) {
                    continue;
                }
                $requestProductItem = new RequestProductItem();
                $requestProductItem->setQuantity($quantity);
                $requestProductItem->setProductUnit($units[$unit]);
                $requestProduct->addRequestProductItem($requestProductItem);
            }

            if (!$requestProduct->getRequestProductItems()->isEmpty()) {
                $request->addRequestProduct($requestProduct);
            }
        }
    }

    /**
     * @param array $ids
     * @return Product[]
     */
    protected function getProducts(array $ids)
    {
        $products = $this->doctrineHelper
            ->getEntityRepositoryForClass('OroB2BProductBundle:Product')
            ->findBy(['id' => $ids]);
        return array_reduce($products, function ($result, Product $product) {
            $result[$product->getId()] = $product;
            return $result;
        }, []);
    }

    /**
     * @param array $productIds
     * @param array $codes
     * @return ProductUnit[]
     */
    protected function getUnits(array $productIds, array $codes)
    {
        /** @var ProductUnitRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass('OroB2BProductBundle:ProductUnit');
        return $repository->getProductsUnitsByCodes($productIds, $codes);
    }
}
