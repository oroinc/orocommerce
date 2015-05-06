<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Controller;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CustomerController extends Controller
{
    /**
     * @Route("/", name="orob2b_customer_admin_customer_index")
     * @Template
     * @AclAncestor("orob2b_customer_admin_customer_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_customer_admin.customer.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_customer_admin_customer_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_admin_customer_view",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:Customer",
     *      permission="VIEW"
     * )
     *
     * @param Customer $customer
     * @return array
     */
    public function viewAction(Customer $customer)
    {
        return [
            'entity' => $customer
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_customer_admin_customer_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_customer_admin_customer_view")
     *
     * @param Customer $customer
     * @return array
     */
    public function infoAction(Customer $customer)
    {
        return [
            'entity' => $customer
        ];
    }
}
