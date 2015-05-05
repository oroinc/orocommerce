<?php

namespace OroB2B\Bundle\UserAdminBundle\Controller\Api\Rest;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Rest\RouteResource("frontenduser")
 * @NamePrefix("orob2b_api_user_admin_")
 */
class UserController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete frontend user",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_user_admin_user_delete",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:User",
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
     *      description="Enable frontend user",
     *      resource=true
     * )
     * @Rest\Get(
     *      "/frontendusers/{id}/enable",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @Acl(
     *      id="orob2b_user_admin_user_enable",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:User",
     *      permission="EDIT"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function enableAction($id)
    {
        $em = $this->get('doctrine')->getManagerForClass('OroB2BUserAdminBundle:User');
        $user = $em->getRepository('OroB2BUserAdminBundle:User')->find($id);

        if (null === $user) {
            return new JsonResponse(
                $this->get('translator')->trans('orob2b.useradmin.controller.user.not_found.message'),
                Codes::HTTP_NOT_FOUND
            );
        }

        $user->setEnabled(true);
        $em->flush();

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')->trans('orob2b.useradmin.controller.user.enabled.message')
                ],
                Codes::HTTP_OK
            )
        );
    }

    /**
     * @ApiDoc(
     *      description="Disable frontend user",
     *      resource=true
     * )
     * @Rest\Get(
     *      "/frontendusers/{id}/disable",
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @AclAncestor("orob2b_user_admin_user_enable")
     *
     * @param int $id
     * @return Response
     */
    public function disableAction($id)
    {
        $em = $this->get('doctrine')->getManagerForClass('OroB2BUserAdminBundle:User');
        $user = $em->getRepository('OroB2BUserAdminBundle:User')->find($id);

        if (null === $user) {
            return new JsonResponse(
                $this->get('translator')->trans('orob2b.useradmin.controller.user.not_found.message'),
                Codes::HTTP_NOT_FOUND
            );
        }

        $user->setEnabled(false);
        $em->flush();

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')->trans('orob2b.useradmin.controller.user.disabled.message')
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
        return $this->get('orob2b_user_admin.user.manager.api');
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
