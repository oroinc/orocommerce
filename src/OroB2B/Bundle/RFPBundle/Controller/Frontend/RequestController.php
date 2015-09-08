<?php

namespace OroB2B\Bundle\RFPBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
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
     * @Template("OroB2BRFPBundle:Request/Frontend:view.html.twig")
     * @Acl(
     *      id="orob2b_rfp_request_frontend_view",
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
            'entity' => $request,
        ];
    }

    /**
     * @Route("/", name="orob2b_rfp_frontend_request_index")
     * @Template("OroB2BRFPBundle:Request/Frontend:index.html.twig")
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
     * @Route("/info/{id}", name="orob2b_rfp_frontend_request_info", requirements={"id"="\d+"})
     * @Template("OroB2BRFPBundle:Request/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_rfp_request_frontend_view")
     *
     * @param RFPRequest $request
     * @return array
     */
    public function infoAction(RFPRequest $request)
    {
        return [
            'entity' => $request
        ];
    }

    /**
     * @Route("/create", name="orob2b_rfp_frontend_request_create")
     * @Template("OroB2BRFPBundle:Request/Frontend:update.html.twig")
     *
     * @return array
     */
    public function createAction()
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
                ->setEmail($user->getEmail())
            ;
        }

        return $this->update($rfpRequest);
    }

    /**
     * @Route("/update/{id}", name="orob2b_rfp_frontend_request_update", requirements={"id"="\d+"})
     * @Template("OroB2BRFPBundle:Request/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_rfp_request_frontend_update",
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
        return $this->update($rfpRequest);
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
            $this->createForm(RequestType::NAME, $rfpRequest),
            function (RFPRequest $rfpRequest) use ($securityFacade) {
                if ($securityFacade->isGranted('ACCOUNT_VIEW', $rfpRequest)) {
                    $route = $securityFacade->isGranted('ACCOUNT_EDIT', $rfpRequest)
                        ? 'orob2b_rfp_frontend_request_update'
                        : 'orob2b_rfp_frontend_request_view'
                    ;

                    return [
                        'route'         => $route,
                        'parameters'    => ['id' => $rfpRequest->getId()],
                    ];
                }

                return [
                    'route'         => 'orob2b_rfp_frontend_request_create',
                    'parameters'    => [],
                ];

            },
            function (RFPRequest $rfpRequest) use ($securityFacade) {
                if ($securityFacade->isGranted('ACCOUNT_VIEW', $rfpRequest)) {
                    return [
                        'route'         => 'orob2b_rfp_frontend_request_view',
                        'parameters'    => ['id' => $rfpRequest->getId()],
                    ];
                }

                return [
                    'route'         => 'orob2b_rfp_frontend_request_create',
                    'parameters'    => [],
                ];
            },
            $this->get('translator')->trans('orob2b.rfp.controller.request.saved.message')
        );
    }

    /**
     * Creates HTMLPurifier
     *
     * @return \HTMLPurifier
     */
    public function getPurifier()
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
            ->findOneBy(['name' => $defaultRequestStatusName])
        ;
    }
}
