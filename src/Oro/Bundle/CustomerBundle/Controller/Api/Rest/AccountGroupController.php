<?php

namespace Oro\Bundle\CustomerBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\CustomerBundle\Event\AccountGroupEvent;

/**
 * @NamePrefix("oro_api_account_")
 */
class AccountGroupController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete account group",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_account_group_delete",
     *      type="entity",
     *      class="OroCustomerBundle:AccountGroup",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->get('oro_api.doctrine_helper')
            ->getEntityRepository('OroCustomerBundle:AccountGroup')
            ->find($id);
        if ($accountGroup) {
            $this->get('event_dispatcher')
                ->dispatch(AccountGroupEvent::PRE_REMOVE, new AccountGroupEvent($accountGroup));
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
