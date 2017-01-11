<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;

class CustomerController extends Controller
{
    /**
     * @Route("/", name="oro_customer_customer_index")
     * @Template
     * @AclAncestor("oro_customer_customer_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_customer.entity.customer.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_customer_customer_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_customer_customer_view",
     *      type="entity",
     *      class="OroCustomerBundle:Customer",
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
     * @Route("/create", name="oro_customer_customer_create")
     * @Template("OroCustomerBundle:Customer:update.html.twig")
     * @Acl(
     *      id="oro_customer_create",
     *      type="entity",
     *      class="OroCustomerBundle:Customer",
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
     * @Route("/update/{id}", name="oro_customer_customer_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_customer_customer_update",
     *      type="entity",
     *      class="OroCustomerBundle:Customer",
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
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $customer,
            $this->createForm(CustomerType::NAME, $customer),
            function (Customer $customer) {
                return [
                    'route' => 'oro_customer_customer_update',
                    'parameters' => ['id' => $customer->getId()],
                ];
            },
            function (Customer $customer) {
                return [
                    'route' => 'oro_customer_customer_view',
                    'parameters' => ['id' => $customer->getId()],
                ];
            },
            $this->get('translator')->trans('oro.customer.controller.customer.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="oro_customer_customer_info", requirements={"id"="\d+"})
     * @Template("OroCustomerBundle:Customer/widget:info.html.twig")
     * @AclAncestor("oro_customer_customer_view")
     *
     * @param Customer $customer
     * @return array
     */
    public function infoAction(Customer $customer)
    {
        return [
            'entity' => $customer,
            'treeData' => $this->get('oro_customer.customer_tree_handler')->createTree($customer),
        ];
    }
}
