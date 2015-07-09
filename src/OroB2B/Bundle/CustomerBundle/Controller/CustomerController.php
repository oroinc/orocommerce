<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerType;

class CustomerController extends Controller
{
    /**
     * @Route("/", name="orob2b_customer_index")
     * @Template
     * @AclAncestor("orob2b_customer_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_customer.entity.customer.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_customer_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_view",
     *      type="entity",
     *      class="OroB2BCustomerBundle:Customer",
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
     * @Route("/create", name="orob2b_customer_create")
     * @Template("OroB2BCustomerBundle:Customer:update.html.twig")
     * @Acl(
     *      id="orob2b_customer_create",
     *      type="entity",
     *      class="OroB2BCustomerBundle:Customer",
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
     * @Route("/update/{id}", name="orob2b_customer_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_update",
     *      type="entity",
     *      class="OroB2BCustomerBundle:Customer",
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
        $options = [];
        $translator = $this->get('translator');
        if ($customer->getGroup() && $customer->getGroup()->getPaymentTerm()) {
            $options['paymentTerm_placeholder'] =
                $translator->trans(
                    'orob2b.customer.payment_term_defined_in_group',
                    ['%payment_term%' => $customer->getGroup()->getPaymentTerm()->getLabel()]
                );
        }
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $customer,
            $this->createForm(
                CustomerType::NAME,
                $customer,
                $options
            ),
            function (Customer $customer) {
                return [
                    'route' => 'orob2b_customer_update',
                    'parameters' => ['id' => $customer->getId()]
                ];
            },
            function (Customer $customer) {
                return [
                    'route' => 'orob2b_customer_view',
                    'parameters' => ['id' => $customer->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.customer.controller.customer.saved.message')
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_customer_info", requirements={"id"="\d+"})
     * @Template("OroB2BCustomerBundle:Customer/widget:info.html.twig")
     * @AclAncestor("orob2b_customer_view")
     *
     * @param Customer $customer
     * @return array
     */
    public function infoAction(Customer $customer)
    {
        return [
            'entity' => $customer,
            'treeData' => $this->get('orob2b_customer.customer_tree_handler')->createTree($customer->getId())
        ];
    }
}
