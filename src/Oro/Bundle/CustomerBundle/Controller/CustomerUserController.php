<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserType;
use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserHandler;

class CustomerUserController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_customer_customer_user_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_customer_customer_user_view",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUser",
     *      permission="VIEW"
     * )
     *
     * @param CustomerUser $customerUser
     * @return array
     */
    public function viewAction(CustomerUser $customerUser)
    {
        return [
            'entity' => $customerUser
        ];
    }

    /**
     * @Route("/", name="oro_customer_customer_user_index")
     * @Template
     * @AclAncestor("oro_customer_customer_user_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_customer.entity.customer_user.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_customer_customer_user_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_customer_customer_user_view")
     *
     * @param CustomerUser $customerUser
     * @return array
     */
    public function infoAction(CustomerUser $customerUser)
    {
        return [
            'entity' => $customerUser
        ];
    }

    /**
     * @Route("/get-roles/{customerUserId}/{customerId}",
     *      name="oro_customer_customer_user_roles",
     *      requirements={"customerId"="\d+", "customerUserId"="\d+"},
     *      defaults={"customerId"=0, "customerUserId"=0}
     * )
     * @Template("OroCustomerBundle:CustomerUser:widget/roles.html.twig")
     * @AclAncestor("oro_customer_customer_user_view")
     *
     * @param Request $request
     * @param string $customerUserId
     * @param string $customerId
     * @return array
     */
    public function getRolesAction(Request $request, $customerUserId, $customerId)
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');

        if ($customerUserId) {
            $customerUser = $doctrineHelper->getEntityReference(
                $this->getParameter('oro_customer.entity.customer_user.class'),
                $customerUserId
            );
        } else {
            $customerUser = new CustomerUser();
        }

        $customer = null;
        if ($customerId) {
            $customer = $doctrineHelper->getEntityReference(
                $this->getParameter('oro_customer.entity.customer.class'),
                $customerId
            );
        }
        $customerUser->setCustomer($customer);

        $form = $this->createForm(CustomerUserType::NAME, $customerUser);

        if (($error = $request->get('error', false)) && $form->has('roles')) {
            $form
                ->get('roles')
                ->addError(new FormError($error));
        }

        return ['form' => $form->createView()];
    }

    /**
     * Create customer user form
     *
     * @Route("/create", name="oro_customer_customer_user_create")
     * @Template("OroCustomerBundle:CustomerUser:update.html.twig")
     * @Acl(
     *      id="oro_customer_customer_user_create",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUser",
     *      permission="CREATE"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new CustomerUser(), $request);
    }

    /**
     * Edit customer user form
     *
     * @Route("/update/{id}", name="oro_customer_customer_user_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_customer_customer_user_update",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerUser",
     *      permission="EDIT"
     * )
     * @param CustomerUser $customerUser
     * @param Request     $request
     * @return array|RedirectResponse
     */
    public function updateAction(CustomerUser $customerUser, Request $request)
    {
        return $this->update($customerUser, $request);
    }

    /**
     * @param CustomerUser $customerUser
     * @param Request     $request
     * @return array|RedirectResponse
     */
    protected function update(CustomerUser $customerUser, Request $request)
    {
        $form = $this->createForm(CustomerUserType::NAME, $customerUser);
        $handler = new CustomerUserHandler(
            $form,
            $request,
            $this->get('oro_customer_user.manager'),
            $this->get('oro_security.security_facade'),
            $this->get('translator'),
            $this->get('logger')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $customerUser,
            $form,
            function (CustomerUser $customerUser) {
                return [
                    'route'      => 'oro_customer_customer_user_update',
                    'parameters' => ['id' => $customerUser->getId()]
                ];
            },
            function (CustomerUser $customerUser) {
                return [
                    'route'      => 'oro_customer_customer_user_view',
                    'parameters' => ['id' => $customerUser->getId()]
                ];
            },
            $this->get('translator')->trans('oro.customer.controller.customeruser.saved.message'),
            $handler
        );

        return $result;
    }
}
