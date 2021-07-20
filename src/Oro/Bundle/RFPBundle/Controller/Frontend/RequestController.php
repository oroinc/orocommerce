<?php

namespace Oro\Bundle\RFPBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CRUD for RFQs on the storefront.
 */
class RequestController extends AbstractController
{
    const LAST_SUCCESS_RFQ_SESSION_NAME = 'last_success_rfq_id';

    /**
     * @Route("/view/{id}", name="oro_rfp_frontend_request_view", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="oro_rfp_frontend_request_view",
     *      type="entity",
     *      class="OroRFPBundle:Request",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param RFPRequest $request
     * @return array
     */
    public function viewAction(RFPRequest $request)
    {
        $this->assertValidInternalStatus($request);

        return [
            'data' => [
                'entity' => $request
            ]
        ];
    }

    /**
     * @AclAncestor("oro_rfp_frontend_request_view")
     * @Route("/", name="oro_rfp_frontend_request_index")
     * @Layout(vars={"entity_class"})
     * @return array|RedirectResponse
     */
    public function indexAction()
    {
        $entityClass = RFPRequest::class;
        $viewPermission = 'VIEW;entity:' . $entityClass;
        if (!$this->isGranted($viewPermission)) {
            return $this->redirect(
                $this->generateUrl('oro_rfp_frontend_request_create')
            );
        }

        return [
            'entity_class' => $entityClass,
        ];
    }

    /**
     * @Acl(
     *      id="oro_rfp_frontend_request_create",
     *      type="entity",
     *      class="OroRFPBundle:Request",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @Route("/create", name="oro_rfp_frontend_request_create")
     * @Layout
     *
     * @param Request $request
     * @return array
     */
    public function createAction(Request $request)
    {
        $rfpRequest = $this->get('oro_rfp.request.manager')->create();
        $this->addProductItemsToRfpRequest($rfpRequest, $request);

        $response = $this->update($rfpRequest);

        if ($response instanceof Response) {
            return $response;
        }

        return ['data' => $response];
    }

    /**
     * @Route("/update/{id}", name="oro_rfp_frontend_request_update", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="oro_rfp_frontend_request_update",
     *      type="entity",
     *      class="OroRFPBundle:Request",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     *
     * @param RFPRequest $rfpRequest
     * @return array|RedirectResponse
     */
    public function updateAction(RFPRequest $rfpRequest)
    {
        $this->assertValidInternalStatus($rfpRequest);

        $response = $this->update($rfpRequest);

        if ($response instanceof Response) {
            return $response;
        }

        return ['data' => $response];
    }

    /**
     * @AclAncestor("oro_rfp_frontend_request_create")
     * @Route("/success", name="oro_rfp_frontend_request_success")
     * @Layout
     *
     * @return array
     */
    public function successAction()
    {
        $rfqID = $this->get('session')->get(self::LAST_SUCCESS_RFQ_SESSION_NAME);
        if ($rfqID !== null) {
            $repository = $this->get('oro_entity.doctrine_helper')->getEntityRepositoryForClass(RFPRequest::class);
            $rfpRequest = $repository->find($rfqID);
            if ($rfpRequest) {
                $this->assertValidInternalStatus($rfpRequest);

                return [
                    'data' => [
                        'entity' => $rfpRequest,
                    ]
                ];
            }
        }

        throw $this->createNotFoundException();
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return array|RedirectResponse
     */
    protected function update(RFPRequest $rfpRequest)
    {
        $handler = $this->get('oro_rfp.service.request_update_handler');
        $isCreateAction = !$rfpRequest->getId();
        $form = $this->get('oro_rfp.layout.data_provider.request_form')->getRequestForm($rfpRequest);

        return $handler->handleUpdate(
            $rfpRequest,
            $form,
            function (RFPRequest $rfpRequest) {
                if ($this->isGranted('EDIT', $rfpRequest)) {
                    return [
                        'route' => 'oro_rfp_frontend_request_update',
                        'parameters' => ['id' => $rfpRequest->getId()],
                    ];
                } elseif ($this->isGranted('VIEW', $rfpRequest)) {
                    return [
                        'route' => 'oro_rfp_frontend_request_view',
                        'parameters' => ['id' => $rfpRequest->getId()],
                    ];
                }

                return [
                    'route' => 'oro_rfp_frontend_request_create',
                    'parameters' => [],
                ];
            },
            function (RFPRequest $rfpRequest) use ($isCreateAction) {
                if ($this->isGranted('VIEW', $rfpRequest)) {
                    return [
                        'route' => 'oro_rfp_frontend_request_view',
                        'parameters' => ['id' => $rfpRequest->getId()],
                    ];
                }

                if ($isCreateAction) {
                    $this->get('session')->set(self::LAST_SUCCESS_RFQ_SESSION_NAME, $rfpRequest->getId());
                    return [
                        'route' => 'oro_rfp_frontend_request_success',
                        'parameters' => [],
                    ];
                }

                return [
                    'route' => 'oro_rfp_frontend_request_create',
                    'parameters' => [],
                ];
            },
            $this->get('translator')->trans('oro.rfp.controller.request.saved.message'),
            null,
            function (RFPRequest $rfpRequest, FormInterface $form, Request $request) {
                $url = $request->headers->get('referer', $request->getUri());

                return [
                    'backToUrl' => $url,
                    'form' => $form->createView()
                ];
            }
        );
    }

    /**
     * @return WebsiteManager
     */
    protected function getWebsiteManager()
    {
        return $this->get('oro_website.manager');
    }

    protected function addProductItemsToRfpRequest(RFPRequest $rfpRequest, Request $request)
    {
        $productLineItems = (array)$request->query->get('product_items', []);
        $filteredProducts = [];
        foreach ($productLineItems as $productId => $items) {
            $productId = (int)$productId;
            if ($productId <= 0) {
                continue;
            }
            $filteredItems = [];
            foreach ($items as $item) {
                if (!is_array($item) || array_diff(['unit', 'quantity'], array_keys($item))) {
                    continue;
                }
                $filteredItems[] = $item;
            }
            if (count($filteredItems) > 0) {
                $filteredProducts[$productId] = $filteredItems;
            }
        }
        if (count($productLineItems) === 0) {
            return;
        }
        $this->get('oro_rfp.request.manager')
            ->addProductLineItemsToRequest($rfpRequest, $filteredProducts);
    }

    /**
     * @throws NotFoundHttpException
     */
    private function assertValidInternalStatus(RFPRequest $request)
    {
        $status = $request->getInternalStatus();
        if ($status && $status->getId() === RFPRequest::INTERNAL_STATUS_DELETED) {
            throw $this->createNotFoundException();
        }
    }
}
