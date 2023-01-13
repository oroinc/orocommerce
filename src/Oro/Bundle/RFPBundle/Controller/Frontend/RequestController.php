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
    private const LAST_SUCCESS_RFQ_SESSION_NAME = 'last_success_rfq_id';

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
     */
    public function viewAction(RFPRequest $request): array
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
     */
    public function indexAction(): array
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
     */
    public function createAction(Request $request): array|Response
    {
        $rfpRequest = $this->get(RequestManager::class)->create();
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
     */
    public function updateAction(RFPRequest $rfpRequest): array|Response
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
     */
    public function successAction(Request $request): array
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

    private function update(RFPRequest $rfpRequest): array|RedirectResponse
    {
        /** @var RequestUpdateHandler $handler */
        $handler = $this->get(RequestUpdateHandler::class);
        $form = $this->get(RFPFormProvider::class)->getRequestForm($rfpRequest);

        return $handler->update(
            $rfpRequest,
            $form,
            $this->get(TranslatorInterface::class)->trans('oro.rfp.controller.request.saved.message'),
            null,
            null,
            function (RFPRequest $rfpRequest, FormInterface $form, Request $request) {
                return [
                    'backToUrl' => $this->get(SameSiteUrlHelper::class)
                        ->getSameSiteReferer($request, $request->getUri()),
                    'form' => $form->createView()
                ];
            }
        );
    }

    private function addProductItemsToRfpRequest(RFPRequest $rfpRequest, Request $request): void
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
    private function assertValidInternalStatus(RFPRequest $request): void
    {
        $status = $request->getInternalStatus();
        if ($status && $status->getId() === RFPRequest::INTERNAL_STATUS_DELETED) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
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
