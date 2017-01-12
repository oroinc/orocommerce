<?php

namespace Oro\Bundle\CustomerBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\CustomerBundle\Event\CustomerGroupEvent;

/**
 * @NamePrefix("oro_api_customer_")
 */
class CustomerGroupController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete customer group",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_customer_customer_group_delete",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerGroup",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->get('oro_api.doctrine_helper')
            ->getEntityRepository('OroCustomerBundle:CustomerGroup')
            ->find($id);
        if ($customerGroup) {
            $this->get('event_dispatcher')
                ->dispatch(CustomerGroupEvent::PRE_REMOVE, new CustomerGroupEvent($customerGroup));
        }

        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_customer.manager.group.api.attribute');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
