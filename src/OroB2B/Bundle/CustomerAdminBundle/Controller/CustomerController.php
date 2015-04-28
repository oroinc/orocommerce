<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Controller;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CustomerController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_customer_admin_customer_view", requirements={"id"="\d+"})
     *
     * @param Customer $customer
     * @return Response
     */
    public function viewAction(Customer $customer)
    {
        return new Response();
    }
}
