<?php

namespace Oro\Bundle\ActionBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionException;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Rest\NamePrefix("oro_api_action_")
 */
class ActionController extends FOSRestController
{
    /**
     * @ApiDoc(description="Execute action", resource=true)
     * @AclAncestor("oro_action")
     * @Rest\Get
     *
     * @param Request $request
     * @param string $actionName
     * @return Response
     */
    public function executeAction(Request $request, $actionName)
    {
        try {
            $context = $this->getActionManager()
                ->execute(
                    [
                        'route' => $request->get('route'),
                        'entityId' => $request->get('entityId'),
                        'entityClass' => $request->get('entityClass')
                    ],
                    $actionName
                );
        } catch (ActionNotFoundException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_NOT_FOUND);
        } catch (ForbiddenActionException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = $context->offsetExists('redirectUrl') ? ['redirectUrl' => $context->offsetGet('redirectUrl')] : [];

        return $this->handleView(
            $this->view($response, Codes::HTTP_OK)
        );
    }

    /**
     * @return ActionManager
     */
    protected function getActionManager()
    {
        return $this->get('oro_action.manager');
    }

    /**
     * @param string $message
     * @param int $code
     * @return Response
     */
    protected function handleError($message, $code)
    {
        return $this->handleView(
            $this->view($this->formatErrorResponse($message), $code)
        );
    }

    /**
     * @param string $message
     * @return array
     */
    protected function formatErrorResponse($message)
    {
        return ['message' => $message];
    }
}
