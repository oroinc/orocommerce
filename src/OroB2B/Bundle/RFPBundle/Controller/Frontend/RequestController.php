<?php

namespace OroB2B\Bundle\RFPBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

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
     * @Route("/create", name="orob2b_rfp_request_process")
     * @Method("POST")
     * @Template("OroB2BRFPBundle:Request/Frontend:create.html.twig")
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function processAction(Request $request)
    {
        $rfpRequest = new RFPRequest();

        $form = $this->createCreateForm($rfpRequest);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('orob2b_rfp.entity.request.class')
            );

            // Clean body from different stuff
            $rfpRequest->setBody($this->getPurifier()->purify($rfpRequest->getBody()));

            $user = $this->getUser();
            if ($user) {
                $rfpRequest->setAccountUser($user);
            } else {
                $rfpRequest->setOrganization(
                    $this->getWebsiteManager()->getCurrentWebsite()->getOrganization()
                );
            }

            $em->persist($rfpRequest);
            $em->flush();

            /** @var User $userForNotification */
            $userForNotification = $this->container->get('oro_config.manager')
                ->get('oro_b2b_rfp.default_user_for_notifications');

            if ($userForNotification) {
                $userForNotification = $this->getDoctrine()
                    ->getRepository($this->container->getParameter('oro_user.entity.class'))
                    ->find($userForNotification->getId());
                $this->container->get('orob2b_rfp.mailer.processor')
                    ->sendRFPNotification($rfpRequest, $userForNotification);
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('orob2b.rfp.request.message.request_saved')
            );

            return $this->redirect($this->generateUrl('orob2b_rfp_request_create'));
        }

        return [
            'entity' => $rfpRequest,
            'form'   => $form->createView()
        ];
    }

    /**
     * Creates form for RFPRequest
     *
     * @param RFPRequest $rfpRequest
     *
     * @return \Symfony\Component\Form\Form
     */
    public function createCreateForm(RFPRequest $rfpRequest)
    {
        $form = $this->createForm(
            FrontendRequestType::NAME,
            $rfpRequest,
            [
                'label' => '',
            ]
        );

        return $form;
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
