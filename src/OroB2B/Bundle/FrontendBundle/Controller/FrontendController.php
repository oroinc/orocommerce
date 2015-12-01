<?php

namespace OroB2B\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FrontendController extends Controller
{
    /**
     * @Route("/", name="_frontend")
     * @return RedirectResponse
     */
    public function indexAction()
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('orob2b_account_account_user_security_login');
        } else {
            return $this->redirectToRoute('orob2b_product_frontend_product_index');
        }
    }
}
