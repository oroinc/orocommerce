<?php

namespace OroB2B\Bundle\RFPBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

use OroB2B\Bundle\RFPBundle\Form\Type\FrontendRequestType;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

class RequestController extends Controller
{
    /**
     * @Route("/create", name="orob2b_rfp_request_create")
     * @Method("GET")
     * @Template("OroB2BRFPBundle:Request/Frontend:create.html.twig")
     *
     * @return array
     */
    public function createAction()
    {
        $rfpRequest = new RFPRequest();

        $form = $this->createCreateForm($rfpRequest);

        return [
            'entity' => $rfpRequest,
            'form'   => $form->createView()
        ];
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
                $rfpRequest->setFrontendOwner($user);
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
                'action' => $this->generateUrl('orob2b_rfp_request_process'),
                'method' => 'POST',
            ]
        );

        $form->add('submit', 'submit', [
            'label' => 'orob2b.rfp.request.submit.label'
        ]);

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
