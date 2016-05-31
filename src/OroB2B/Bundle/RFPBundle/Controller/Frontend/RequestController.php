<?php

namespace OroB2B\Bundle\RFPBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestUpdateHandler;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class RequestController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_rfp_frontend_request_view", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="orob2b_rfp_frontend_request_view",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     *
     * @param RFPRequest $request
     * @return array
     */
    public function viewAction(RFPRequest $request)
    {
        return [
            'data' => [
                'entity' => $request
            ]
        ];
    }

    /**
     * @AclAncestor("orob2b_rfp_frontend_request_view")
     * @Route("/", name="orob2b_rfp_frontend_request_index")
     * @Layout(vars={"entity_class"})
     * @return array
     */
    public function indexAction()
    {
        $entityClass = $this->container->getParameter('orob2b_rfp.entity.request.class');
        $viewPermission = 'VIEW;entity:' . $entityClass;
        if (!$this->getSecurityFacade()->isGranted($viewPermission)) {
            return $this->redirect(
                $this->generateUrl('orob2b_rfp_frontend_request_create')
            );
        }

        return [
            'entity_class' => $entityClass,
        ];
    }

    /**
     * @Acl(
     *      id="orob2b_rfp_frontend_request_create",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @Route("/create", name="orob2b_rfp_frontend_request_create")
     * @Layout
     *
     * @param Request $request
     * @return array
     */
    public function createAction(Request $request)
    {
        $rfpRequest = $this->get('orob2b_rfp.request.manager')->create();
        $this->addProductItemsToRfpRequest($rfpRequest, $request);

        $response = $this->update($rfpRequest);

        if ($response instanceof Response) {
            return $response;
        }

        return ['data' => $response];
    }

    /**
     * @Route("/update/{id}", name="orob2b_rfp_frontend_request_update", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="orob2b_rfp_frontend_request_update",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
     *      permission="ACCOUNT_EDIT",
     *      group_name="commerce"
     * )
     *
     * @param RFPRequest $rfpRequest
     * @return array|RedirectResponse
     */
    public function updateAction(RFPRequest $rfpRequest)
    {
        $response = $this->update($rfpRequest);

        if ($response instanceof Response) {
            return $response;
        }

        return ['data' => $response];
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return array|RedirectResponse
     */
    protected function update(RFPRequest $rfpRequest)
    {
        /* @var $handler RequestUpdateHandler */
        $handler = $this->get('orob2b_rfp.service.request_update_handler');

        // set default status after edit
        if ($rfpRequest->getId()) {
            $rfpRequest->setStatus($this->getDefaultRequestStatus());
        }

        $securityFacade = $this->getSecurityFacade();

        return $handler->handleUpdate(
            $rfpRequest,
            $this->get('orob2b_rfp.layout.data_provider.request_form')->getForm($rfpRequest),
            function (RFPRequest $rfpRequest) use ($securityFacade) {
                if ($securityFacade->isGranted('ACCOUNT_VIEW', $rfpRequest)) {
                    $route = $securityFacade->isGranted('ACCOUNT_EDIT', $rfpRequest)
                        ? 'orob2b_rfp_frontend_request_update'
                        : 'orob2b_rfp_frontend_request_view';

                    return [
                        'route' => $route,
                        'parameters' => ['id' => $rfpRequest->getId()],
                    ];
                }

                return [
                    'route' => 'orob2b_rfp_frontend_request_create',
                    'parameters' => [],
                ];
            },
            function (RFPRequest $rfpRequest) use ($securityFacade) {
                if ($securityFacade->isGranted('ACCOUNT_VIEW', $rfpRequest)) {
                    return [
                        'route' => 'orob2b_rfp_frontend_request_view',
                        'parameters' => ['id' => $rfpRequest->getId()],
                    ];
                }

                return [
                    'route' => 'orob2b_rfp_frontend_request_create',
                    'parameters' => [],
                ];
            },
            $this->get('translator')->trans('orob2b.rfp.controller.request.saved.message'),
            null,
            function (RFPRequest $rfpRequest, FormInterface $form, Request $request) {
                $url = $request->getUri();
                if ($request->headers->get('referer')) {
                    $url = $request->headers->get('referer');
                }

                return [
                    'backToUrl' => $url,
                    'form' => $form->createView()
                ];
            }
        );
    }

    /**
     * Creates HTMLPurifier
     *
     * @return \HTMLPurifier
     */
    protected function getPurifier()
    {
        $purifierConfig = \HTMLPurifier_Config::createDefault();
        $purifierConfig->set('HTML.Allowed', '');

        return new \HTMLPurifier($purifierConfig);
    }

    /**
     * @return WebsiteManager
     */
    protected function getWebsiteManager()
    {
        return $this->get('orob2b_website.manager');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->get('oro_security.security_facade');
    }

    /**
     * @return RequestStatus
     */
    protected function getDefaultRequestStatus()
    {
        $requestStatusClass = $this->container->getParameter('orob2b_rfp.entity.request.status.class');
        $defaultRequestStatusName = $this->get('oro_config.manager')->get('oro_b2b_rfp.default_request_status');

        return $this
            ->getDoctrine()
            ->getManagerForClass($requestStatusClass)
            ->getRepository($requestStatusClass)
            ->findOneBy(['name' => $defaultRequestStatusName]);
    }

    /**
     * @param RFPRequest $rfpRequest
     * @param Request $request
     */
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
        $this->get('orob2b_rfp.request.manager')
            ->addProductLineItemsToRequest($rfpRequest, $filteredProducts);
    }
}
