<?php

namespace OroB2B\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends Controller
{
    /**
     * @Route("/", name="_frontend")
     * @Template()
     * @return array
     */
    public function indexAction()
    {
        return [];
    }
}
