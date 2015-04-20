<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

/**
 * @Route("/rfp")
 */
class RequestController extends Controller
{
    /**
     * @Route("/", name="orob2b_rfp_reuest_index")
     */
    public function indexAction()
    {
        return new Response('Index!');
    }

    /**
     * @Route("/create", name="orob2b_rfp_reuqest_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $rfpRequest = new RFPRequest();

        $defaultStatusName = $this->get('oro_config.fake_manager')->get('oro_b2b_rfp_admin.default_request_status');

        $defaultStatus = $this->getDoctrine()
            ->getManagerForClass('OroB2BRFPBundle:RequestStatus')
            ->getRepository('OroB2BRFPBundle:RequestStatus')
            ->findOneBy(['name' => $defaultStatusName]);

        $rfpRequest->setStatus($defaultStatus);

        $form = $this->createCreateForm($rfpRequest);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManagerForClass('OroB2BRFPBundle:Request');
            $em->persist($rfpRequest);
            $em->flush();

            return $this->redirect($this->generateUrl('orob2b_rfp_reuest_index'));
        }

        return [
            'entity' => $rfpRequest,
            'form'   => $form->createView()
        ];
    }

    public function createCreateForm(RFPRequest $rfpRequest)
    {
        $form = $this->createForm(
            'orob2b_rfp_request_type',
            $rfpRequest,
            [
                'action' => $this->generateUrl('orob2b_rfp_reuqest_create'),
                'method' => 'POST',
            ]
        );

        $form->add('submit', 'submit');

        return $form;
    }
}
