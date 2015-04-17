<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class RequestController extends Controller
{
    /**
     * @Route("/rfp", name="orob2b_rfp_index")
     */
    public function indexAction()
    {
        return new Response('Index!');
    }
}
