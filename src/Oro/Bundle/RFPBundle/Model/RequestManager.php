<?php

namespace Oro\Bundle\RFPBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class RequestManager
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var GuestCustomerUserManager */
    protected $guestCustomerUserManager;

    /** @var WebsiteManager */
    protected $websiteManager;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        DoctrineHelper $doctrineHelper,
        GuestCustomerUserManager $guestCustomerUserManager,
        WebsiteManager $websiteManager
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->doctrineHelper = $doctrineHelper;
        $this->guestCustomerUserManager = $guestCustomerUserManager;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @return Request
     */
    public function create()
    {
        $request = new Request();
        $user = $this->tokenAccessor->getUser();
        if ($user instanceof CustomerUser) {
            $request
                ->setCustomerUser($user)
                ->setCustomer($user->getCustomer())
                ->setFirstName($user->getFirstName())
                ->setLastName($user->getLastName())
                ->setCompany($user->getCustomer()->getName())
                ->setEmail($user->getEmail());
        }

        $request->setWebsite($this->websiteManager->getCurrentWebsite());

        return $request;
    }

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
            ->getEntityRepositoryForClass('OroProductBundle:Product')
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
        $repository = $this->doctrineHelper->getEntityRepositoryForClass('OroProductBundle:ProductUnit');
        return $repository->getProductsUnitsByCodes($productIds, $codes);
    }
}
