<?php

namespace OroB2B\Bundle\RFPBundle\Duplicator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestDuplicator
{
    /**
     * @var
     */
    protected $requestStatusRepository;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Request $request
     * @return Request
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function duplicate(Request $request)
    {
        $objectManager = $this->doctrineHelper->getEntityManager($request);
        $objectManager->getConnection()->beginTransaction();

        try {
            $requestCopy = $this->createRequestCopy($request);

            $objectManager->persist($requestCopy);
            $objectManager->flush();

            $objectManager->getConnection()->commit();
        } catch (\Exception $e) {
            $objectManager->getConnection()->rollBack();
            throw $e;
        }

        return $requestCopy;
    }


    /**
     * @param Request $request
     * @return Request
     */
    protected function createRequestCopy(Request $request)
    {
        $requestCopy = clone $request;
        /** @var  $status RequestStatus */
        $status = $this->getRequestStatusRepository()->findOneBy(['name' => RequestStatus::OPEN]);
        $requestCopy->setStatus($status);

        $this->cloneChildObjects($request, $requestCopy);

        return $requestCopy;
    }


    /**
     * @param Request $request
     * @param Request $requestCopy
     */
    protected function cloneChildObjects(Request $request, Request $requestCopy)
    {
        foreach ($request->getRequestProducts() as $requestProduct) {
            $requestProductClone = $this->getCloneRequestProduct($requestProduct);
            $requestCopy->addRequestProduct($requestProductClone);
        }
        foreach ($request->getAssignedUsers() as $user) {
            $requestCopy->addAssignedUser($user);
        }
        foreach ($request->getAssignedAccountUsers() as $accountUser) {
            $requestCopy->addAssignedAccountUser($accountUser);
        }
    }

    /**
     * @param RequestProduct $requestProduct
     * @return RequestProduct
     */
    protected function getCloneRequestProduct(RequestProduct $requestProduct)
    {
        $cloneRequestProduct = clone $requestProduct;
        foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
            $cloneRequestProductItem = clone $requestProductItem;
            $cloneRequestProductItem->setRequestProduct(null);
            $cloneRequestProduct->addRequestProductItem($cloneRequestProductItem);
        }
        return $cloneRequestProduct;
    }

    /**
     * @return RequestStatusRepository
     */
    protected function getRequestStatusRepository()
    {
        if (!$this->requestStatusRepository) {
            $this->requestStatusRepository = $this->doctrineHelper
                ->getEntityRepository('OroB2BRFPBundle:RequestStatus');
        }

        return $this->requestStatusRepository;
    }
}
