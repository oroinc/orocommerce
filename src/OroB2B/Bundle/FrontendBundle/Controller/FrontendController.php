<?php

namespace OroB2B\Bundle\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends Controller
{
    /**
     * @Route("/", name="_frontend")
     */
    public function indexAction()
    {
        return new Response('Oro Frontend!');
    }
}
