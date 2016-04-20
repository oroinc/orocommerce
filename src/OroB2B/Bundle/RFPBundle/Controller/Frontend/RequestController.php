<?php

namespace OroB2B\Bundle\RFPBundle\Controller\Frontend;

use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
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
        $rfpRequest = new RFPRequest();
        $user = $this->getUser();
        if ($user instanceof AccountUser) {
            $rfpRequest
                ->setAccountUser($user)
                ->setAccount($user->getAccount())
                ->setFirstName($user->getFirstName())
                ->setLastName($user->getLastName())
                ->setCompany($user->getAccount()->getName())
                ->setEmail($user->getEmail());
        }

        $this->acceptLineItemsOnCreate($request, $rfpRequest);

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
        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');

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
     * @param Request $request
     * @param RFPRequest $rfpRequest
     */
    protected function acceptLineItemsOnCreate(Request $request, RFPRequest $rfpRequest)
    {
        if ($request->getMethod() !== 'GET') {
            return;
        }

        $lienItems = $request->get('product_items', []);
        foreach ($lienItems as $lineItem) {
            $keys = [
                'product_id',
                'unit',
                'quantity',
            ];
            if (count(array_intersect($keys, array_keys($lineItem))) !== count($keys)) {
                continue;
            }
            $product = $this->container->get('doctrine')->getManagerForClass('OroB2BProductBundle:Product')
                ->getRepository('OroB2BProductBundle:Product')->find($lineItem['product_id']);
            $unit = $this->container->get('doctrine')
                ->getManagerForClass('OroB2BProductBundle:ProductUnit')
                ->getReference('OroB2BProductBundle:ProductUnit', $lineItem['unit']);
            $requestProductItem = new RequestProductItem();
            $requestProductItem->setQuantity($lineItem['quantity']);
            $requestProductItem->setProductUnit($unit);
            $requestProduct = new RequestProduct();
            $requestProduct->setProduct($product);
            $requestProduct->addRequestProductItem($requestProductItem);
            $rfpRequest->addRequestProduct($requestProduct);
        }
    }
}
