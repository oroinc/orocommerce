<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup;

class CustomerGroupController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_customer_admin_group_view", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="orob2b_customer_admin_group_view",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:CustomerGroup",
     *      permission="VIEW"
     * )
     *
     * @param CustomerGroup $group
     * @return Response
     */
    public function viewAction(CustomerGroup $group)
    {
        return new Response();
    }
}
