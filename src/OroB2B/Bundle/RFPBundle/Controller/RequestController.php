<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

class RequestController extends Controller
{
    /**
     * @Route("/create", name="orob2b_rfp_request_create")
     * @Method("GET")
     * @Template()
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
     * @Template("OroB2BRFPBundle:Request:create.html.twig")
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
            $em = $this->getDoctrine()->getManagerForClass('OroB2BRFPBundle:Request');

            // Clean body from different stuff
            $rfpRequest->setBody($this->getPurifier()->purify($rfpRequest->getBody()));

            $em->persist($rfpRequest);
            $em->flush();

            $this->get('orob2b_email.email_send_processor')->sendRequestCreateNotification($rfpRequest);

            $request->getSession()->getFlashBag()->add(
                'notice',
                $this->get('translator')->trans('orob2b.rfp.message.request_saved')
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
            'orob2b_rfp_request_type',
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
}
