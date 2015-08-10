<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class AjaxOrderController extends Controller
{
    /**
     * Get order subtotals
     *
     * @Route("/subtotals", name="orob2b_order_subtotals")
     * @Method({"POST"})
     * @AclAncestor("orob2b_order_update")
     *
     * @return JsonResponse
     */
    public function subtotalsAction()
    {
        $orderClass = $this->getParameter('orob2b_order.entity.order.class');
        $id = $this->get('request')->get('id');
        if ($id) {
            /** @var Order $order */
            $order = $this->getDoctrine()->getManagerForClass($orderClass)->find($orderClass, $id);
        } else {
            $order = new $orderClass();
        }

        $form = $this->createForm(OrderType::NAME, $order);
        $form->submit($this->get('request'));

        if ($form->isValid()) {
            $subtotals = $this->get('orob2b_order.provider.subtotals')->getSubtotals($order);
            $subtotals = $subtotals->map(
                function (Subtotal $subtotal) {
                    return $subtotal->toArray();
                }
            )->toArray();
        } else {
            $subtotals = false;
        }

        return new JsonResponse(['subtotals' => $subtotals]);
    }

    /**
     * Get order related data
     *
     * @Route(
     *      "/related-data/{accountId}/{accountUserId}",
     *      name="orob2b_order_related_data",
     *      requirements={"accountId"="\d+", "accountUserId"="\d+"},
     *      defaults={"accountUserId"=0}
     * )
     * @Method({"GET"})
     * @AclAncestor("orob2b_order_update")
     *
     * @ParamConverter("account", options={"id" = "accountId"})
     * @ParamConverter("accountUser", options={"id" = "accountUserId"})
     *
     * @param Account $account
     * @param AccountUser|null $accountUser
     * @return JsonResponse
     */
    public function getRelatedDataAction(Account $account, AccountUser $accountUser = null)
    {
        $order = new Order();
        $order->setAccount($account)->setAccountUser($accountUser);

        $paymentTerm = $this->getPaymentTermProvider()->getPaymentTerm($account);

        return new JsonResponse(
            [
                'billingAddress' => $this->renderForm(
                    $this->createPriceListForm($order, AddressType::TYPE_BILLING)->createView()
                ),
                'shippingAddress' => $this->renderForm(
                    $this->createPriceListForm($order, AddressType::TYPE_SHIPPING)->createView()
                ),
                'paymentTerm' => $paymentTerm ? $paymentTerm->getId() : null,
            ]
        );
    }

    /**
     * @param Order $order
     * @param string $type
     * @return Form
     */
    protected function createPriceListForm(Order $order, $type)
    {
        return $this->createForm(
            OrderAddressType::NAME,
            null,
            [
                'label' => 'orob2b.order.' . $type . '_address.label',
                'order' => $order,
                'required' => false,
                'addressType' => $type,
            ]
        );
    }

    /**
     * @param FormView $formView
     * @return string
     */
    protected function renderForm(FormView $formView)
    {
        return $this->renderView('OroB2BOrderBundle:Form:accountAddressSelector.html.twig', ['form' => $formView]);
    }

    /**
     * @return PaymentTermProvider
     */
    protected function getPaymentTermProvider()
    {
        return $this->get('orob2b_payment.provider.payment_term');
    }
}
