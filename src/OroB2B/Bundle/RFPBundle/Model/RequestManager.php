<?php

namespace OroB2B\Bundle\RFPBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
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
     * @param int $productId
     * @param string $unit
     * @param string $quantity
     */
    public function addProductItemToRequest(Request $request, $productId, $unit, $quantity)
    {
        $product = $this->getProduct($productId);
        $unit = $this->getUnitReference($unit);

        $requestProductItem = new RequestProductItem();
        $requestProductItem->setQuantity($quantity);
        $requestProductItem->setProductUnit($unit);

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->addRequestProductItem($requestProductItem);

        $request->addRequestProduct($requestProduct);
    }

    /**
     * @param $id
     * @return Product
     */
    protected function getProduct($id)
    {
        return $this->doctrineHelper
            ->getEntityRepositoryForClass('OroB2BProductBundle:Product')
            ->find($id);
    }

    /**
     * @param $id
     * @return ProductUnit
     */
    protected function getUnitReference($id)
    {
        return $this->doctrineHelper->getEntityReference('OroB2BProductBundle:ProductUnit', $id);
    }
}
