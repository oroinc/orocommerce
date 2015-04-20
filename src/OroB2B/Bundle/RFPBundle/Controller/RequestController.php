<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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
}
