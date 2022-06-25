<?php

namespace Oro\Bundle\RFPBundle\Controller\Frontend;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Form\Handler\RequestUpdateHandler;
use Oro\Bundle\RFPBundle\Layout\DataProvider\RFPFormProvider;
use Oro\Bundle\RFPBundle\Model\RequestManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => RFPRequest::class
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
     * @return array|Response
     */
    public function createAction(Request $request)
    {
        $rfpRequest = $this->get(RequestManager::class)->create();
        $this->addProductItemsToRfpRequest($rfpRequest, $request);

        $response = $this->update($rfpRequest, $request);

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
    public function updateAction(RFPRequest $rfpRequest, Request $request)
    {
        $this->assertValidInternalStatus($rfpRequest);

        $response = $this->update($rfpRequest, $request);

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
    public function successAction(Request $request)
    {
        $rfqID = $request->getSession()->get(self::LAST_SUCCESS_RFQ_SESSION_NAME);
        if ($rfqID !== null) {
            $repository = $this->get(DoctrineHelper::class)->getEntityRepositoryForClass(RFPRequest::class);
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
    protected function update(RFPRequest $rfpRequest, Request $request)
    {
        $handler = $this->get(RequestUpdateHandler::class);
        $isCreateAction = !$rfpRequest->getId();
        $form = $this->get(RFPFormProvider::class)->getRequestForm($rfpRequest);

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
            function (RFPRequest $rfpRequest) use ($isCreateAction, $request) {
                if ($this->isGranted('VIEW', $rfpRequest)) {
                    return [
                        'route' => 'oro_rfp_frontend_request_view',
                        'parameters' => ['id' => $rfpRequest->getId()],
                    ];
                }

                if ($isCreateAction) {
                    $request->getSession()->set(self::LAST_SUCCESS_RFQ_SESSION_NAME, $rfpRequest->getId());

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
            $this->get(TranslatorInterface::class)->trans('oro.rfp.controller.request.saved.message'),
            null,
            function (RFPRequest $rfpRequest, FormInterface $form, Request $request) {
                return [
                    'backToUrl' => $this->get(SameSiteUrlHelper::class)
                        ->getSameSiteReferer($request, $request->getUri()),
                    'form' => $form->createView(),
                ];
            }
        );
    }

    protected function getWebsiteManager(): WebsiteManager
    {
        return $this->get(WebsiteManager::class);
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
        $this->get(RequestManager::class)
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                DoctrineHelper::class,
                RequestUpdateHandler::class,
                RFPFormProvider::class,
                WebsiteManager::class,
                RequestManager::class,
                SameSiteUrlHelper::class,
            ]
        );
    }
}
