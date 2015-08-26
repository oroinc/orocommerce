<?php

namespace OroB2B\Bundle\RFPBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Form\Type\FrontendRequestType;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class RequestController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_rfp_frontend_request_view", requirements={"id"="\d+"})
     * @Template("OroB2BRFPBundle:Request/Frontend:view.html.twig")
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
        return [
            'entity_class' => $this->container->getParameter('orob2b_rfp.entity.request.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_rfp_frontend_request_info", requirements={"id"="\d+"})
     * @Template("OroB2BRFPBundle:Request/Frontend/widget:info.html.twig")
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
                ->setCompany($user->getOrganization()->getName())
                ->setEmail($user->getEmail())
            ;
        }

        return $this->update($rfpRequest);
    }

    /**
     * @Route("/update/{id}", name="orob2b_rfp_frontend_request_update", requirements={"id"="\d+"})
     * @Template("OroB2BRFPBundle:Request/Frontend:update.html.twig")
     * @param RFPRequest $rfpRequest
     *
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
        return $handler->handleUpdate(
            $rfpRequest,
            $this->createForm(FrontendRequestType::NAME, $rfpRequest),
            function (RFPRequest $rfpRequest) {
                return [
                    'route'         => 'orob2b_rfp_frontend_request_update',
                    'parameters'    => ['id' => $rfpRequest->getId()]
                ];
            },
            function (RFPRequest $rfpRequest) {
                return [
                    'route'         => 'orob2b_rfp_frontend_request_view',
                    'parameters'    => ['id' => $rfpRequest->getId()]
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
}
