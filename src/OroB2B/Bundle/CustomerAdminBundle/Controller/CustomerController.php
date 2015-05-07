<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Controller;

use Doctrine\Common\Util\ClassUtils;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;
use OroB2B\Bundle\CustomerAdminBundle\Form\Handler\CustomerHandler;
use OroB2B\Bundle\CustomerAdminBundle\Form\Type\CustomerType;

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
            'entity_class' => $this->container->getParameter('orob2b_customer_admin.customer.class'),
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
            'entity' => $customer,
        ];
    }

    /**
     * @Route("/create", name="orob2b_customer_admin_customer_create")
     * @Template("OroB2BCustomerAdminBundle:Customer:update.html.twig")
     * @Acl(
     *      id="orob2b_customer_admin_customer_create",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:Customer",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new Customer());
    }

    /**
     * @Route("/update/{id}", name="orob2b_customer_admin_customer_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_admin_customer_update",
     *      type="entity",
     *      class="OroB2BCustomerAdminBundle:Customer",
     *      permission="EDIT"
     * )
     *
     * @param Customer $customer
     * @return array
     */
    public function updateAction(Customer $customer)
    {
        return $this->update($customer);
    }

    /**
     * @param Customer $customer
     * @return array|RedirectResponse
     */
    protected function update(Customer $customer)
    {
        $form = $this->createForm(CustomerType::NAME, $customer);
        $handler = new CustomerHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($customer))
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $customer,
            $form,
            function (Customer $customer) {
                return [
                    'route' => 'orob2b_customer_admin_customer_update',
                    'parameters' => ['id' => $customer->getId()],
                ];
            },
            function (Customer $customer) {
                return [
                    'route' => 'orob2b_customer_admin_customer_view',
                    'parameters' => ['id' => $customer->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.customeradmin.controller.customer.saved.message'),
            $handler
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_customer_admin_customer_info", requirements={"id"="\d+"})
     * @Template("OroB2BCustomerAdminBundle:Customer/widget:info.html.twig")
     * @AclAncestor("orob2b_customer_admin_customer_view")
     *
     * @param Customer $customer
     * @return array
     */
    public function infoAction(Customer $customer)
    {
        return [
            'entity' => $customer,
            'treeData' => $this->get('orob2b_customer_admin.customer_tree_handler')->createTree($customer->getId()),
        ];
    }
}
