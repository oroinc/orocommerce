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
     * @var RequestStatusRepository
     */
    protected $requestStatusRepository;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper) {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Request $request
     * @param array $excludedFields
     * @return Request
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function duplicate(Request $request, array $excludedFields = [])
    {
        $objectManager = $this->doctrineHelper->getEntityManager($request);
        $objectManager->getConnection()->beginTransaction();

        try {
            $requestCopy = $this->createRequestCopy($request, $excludedFields);

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
     * @param array $excludedFields
     * @return Request
     */
    protected function createRequestCopy(Request $request, array $excludedFields)
    {
        $requestCopy = clone $request;
        /** @var $status RequestStatus */
        if (in_array('status', $excludedFields)) {
            $status = $this->getRequestStatusRepository()->findOneBy(['name' => RequestStatus::OPEN]);
            $requestCopy->setStatus($status);
            $key = array_search('status', $excludedFields);
            unset($excludedFields[$key]);
        }
        $this->cloneChildObjects($request, $requestCopy, $excludedFields);


        return $requestCopy;
    }


    /**
     * @param Request $request
     * @param Request $requestCopy
     * @param array $excludedFields
     */
    protected function cloneChildObjects(Request $request, Request $requestCopy, array $excludedFields)
    {
        if (!in_array('requestProducts', $excludedFields)) {
            foreach ($request->getRequestProducts() as $requestProduct) {
                $requestProductClone = $this->getCloneRequestProduct($requestProduct);
                $requestCopy->addRequestProduct($requestProductClone);
            }
        } else {
            $key = array_search('requestProducts', $excludedFields);
            unset($excludedFields[$key]);
        }

        if (!in_array('assignedUsers', $excludedFields)) {
            foreach ($request->getAssignedUsers() as $user) {
                $requestCopy->addAssignedUser($user);
            }
        } else {
            $key = array_search('assignedUsers', $excludedFields);
            unset($excludedFields[$key]);
        }

        if (!in_array('assignedAccountUsers', $excludedFields)) {
            foreach ($request->getAssignedAccountUsers() as $accountUser) {
                $requestCopy->addAssignedAccountUser($accountUser);
            }
        } else {
            $key = array_search('assignedAccountUsers', $excludedFields);
            unset($excludedFields[$key]);
        }
        $this->resetExcludedFields($requestCopy, $excludedFields);
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

    protected function resetExcludedFields(Request $requestCopy, array $excludedFields)
    {
        $key = array_search('id', $excludedFields);
        unset($excludedFields[$key]);
        foreach ($excludedFields as $field) {
            $setter = 'set' . $field;
            if (method_exists($requestCopy, $setter)) {
                $requestCopy->$setter(null);
            } else {
                throw new \InvalidArgumentException($field . ' field not found');
            }
        }
    }
}
