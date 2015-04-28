<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Controller;

use OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CustomerGroupController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_customer_admin_group_view", requirements={"id"="\d+"})
     *
     * @param CustomerGroup $group
     * @return Response
     */
    public function viewAction(CustomerGroup $group)
    {
        return new Response();
    }
}
