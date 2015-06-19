<?php

namespace OroB2B\Bundle\CustomerBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("orob2b_api_customer_")
 */
class AccountUserController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete account user",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_customer_account_user_delete",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @ApiDoc(
     *      description="Enable account user",
     *      resource=true
     * )
     * @Rest\Get(
     *      "/account/user/{id}/enable",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @Acl(
     *      id="orob2b_customer_account_user_enable",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="EDIT"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function enableAction($id)
    {
        $enableMessage = $this->get('translator')->trans('orob2b.customer.controller.accountuser.enabled.message');
        return $this->enableTrigger($id, true, $enableMessage);
    }

    /**
     * @ApiDoc(
     *      description="Disable account user",
     *      resource=true
     * )
     * @Rest\Get(
     *      "/account/user/{id}/disable",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @AclAncestor("orob2b_customer_account_user_enable")
     *
     * @param int $id
     * @return Response
     */
    public function disableAction($id)
    {
        $disableMessage = $this->get('translator')->trans('orob2b.customer.controller.accountuser.disabled.message');
        return $this->enableTrigger($id, false, $disableMessage);
    }

    /**
     * @ApiDoc(
     *      description="Confirm account user",
     *      resource=true
     * )
     * @Rest\Get(
     *      "/account/user/{id}/confirm",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @Acl(
     *      id="orob2b_customer_account_user_confirm",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="EDIT"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function confirmAction($id)
    {
        $userManager = $this->get('orob2b_account_user.manager');
        $user = $userManager->findUserBy(['id' => $id]);

        if (null === $user) {
            return $this->handleView(
                $this->view(['successful' => false], Codes::HTTP_NOT_FOUND)
            );
        }

        $userManager->confirmRegistration($user);
        $userManager->updateUser($user);

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')
                        ->trans('orob2b.customer.controller.accountuser.confirmed.message')
                ],
                Codes::HTTP_OK
            )
        );
    }

    /**
     * @param integer $id
     * @param boolean $enabled
     * @param string $successMessage
     * @return Response
     */
    protected function enableTrigger($id, $enabled, $successMessage)
    {
        $em = $this->get('doctrine')->getManagerForClass('OroB2BCustomerBundle:AccountUser');
        $user = $em->getRepository('OroB2BCustomerBundle:AccountUser')->find($id);

        if (null === $user) {
            return $this->handleView(
                $this->view(['successful' => false], Codes::HTTP_NOT_FOUND)
            );
        }

        $user->setEnabled($enabled);
        $em->flush();

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $successMessage
                ],
                Codes::HTTP_OK
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_customer.account_user.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
