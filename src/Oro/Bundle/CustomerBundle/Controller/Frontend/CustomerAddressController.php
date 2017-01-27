<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;

class CustomerAddressController extends Controller
{
    /**
     * @Route(
     *     "/{entityId}/create",
     *     name="oro_customer_frontend_customer_address_create",
     *     requirements={"entityId":"\d+"}
     * )
     * @Acl(
     *      id="oro_customer_frontend_customer_address_create",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerAddress",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @Layout
     *
     * @ParamConverter("customer", options={"id" = "entityId"})
     *
     * @param Customer $customer
     * @param Request $request
     * @return array
     */
    public function createAction(Customer $customer, Request $request)
    {
        return $this->update($customer, new CustomerAddress(), $request);
    }

    /**
     * @Route(
     *     "/{entityId}/update/{id}",
     *     name="oro_customer_frontend_customer_address_update",
     *     requirements={"entityId":"\d+", "id":"\d+"}
     * )
     * @Acl(
     *      id="oro_customer_frontend_customer_address_update",
     *      type="entity",
     *      class="OroCustomerBundle:CustomerAddress",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     * @Layout
     *
     * @ParamConverter("customer", options={"id" = "entityId"})
     * @ParamConverter("customerAddress", options={"id" = "id"})
     *
     * @param Customer $customer
     * @param CustomerAddress $customerAddress
     * @param Request $request
     * @return array
     */
    public function updateAction(Customer $customer, CustomerAddress $customerAddress, Request $request)
    {
        return $this->update($customer, $customerAddress, $request);
    }

    /**
     * @param Customer $customer
     * @param CustomerAddress $customerAddress
     * @param Request $request
     * @return array
     */
    private function update(Customer $customer, CustomerAddress $customerAddress, Request $request)
    {
        $this->prepareEntities($customer, $customerAddress, $request);

        $form = $this->get('oro_customer.provider.frontend_customer_address_form')
            ->getAddressForm($customerAddress, $customer);

        $manager = $this->getDoctrine()->getManagerForClass(
            $this->container->getParameter('oro_customer.entity.customer_address.class')
        );

        $handler = new AddressHandler($form, $request, $manager);

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            function (CustomerAddress $customerAddress) use ($customer) {
                return [
                    'route' => 'oro_customer_frontend_customer_address_update',
                    'parameters' => ['id' => $customerAddress->getId(), 'entityId' => $customer->getId()],
                ];
            },
            function (CustomerAddress $customerAddress) {
                return [
                    'route' => 'oro_customer_frontend_customer_user_address_index'
                ];
            },
            $this->get('translator')->trans('oro.customer.controller.customeraddress.saved.message'),
            $handler,
            function (CustomerAddress $customerAddress, FormInterface $form, Request $request) {
                $url = $request->getUri();
                if ($request->headers->get('referer')) {
                    $url = $request->headers->get('referer');
                }

                return [
                    'backToUrl' => $url
                ];
            }
        );

        if ($result instanceof Response) {
            return $result;
        }

        return [
            'data' => array_merge($result, ['customer' => $customer])
        ];
    }

    /**
     * @param Customer $customer
     * @param CustomerAddress $customerAddress
     * @param Request $request
     */
    private function prepareEntities(Customer $customer, CustomerAddress $customerAddress, Request $request)
    {
        if ($request->getMethod() === 'GET' && !$customerAddress->getId()) {
            $customerAddress->setFirstName($customer->getOwner()->getFirstName());
            $customerAddress->setLastName($customer->getOwner()->getLastName());
            if (!$customer->getAddresses()->count()) {
                $customerAddress->setPrimary(true);
            }
        }

        if (!$customerAddress->getFrontendOwner()) {
            $customer->addAddress($customerAddress);
        } elseif ($customerAddress->getFrontendOwner()->getId() !== $customer->getId()) {
            throw new BadRequestHttpException('Address must belong to Customer');
        }
    }
}
