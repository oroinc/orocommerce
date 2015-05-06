<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

class CustomerController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_customer_admin_customer_view", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="orob2b_customer_admin_customer_view",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:Customer",
     *      permission="VIEW"
     * )
     *
     * @param Customer $customer
     * @return Response
     */
    public function viewAction(Customer $customer)
    {
        return new Response();
    }
}
