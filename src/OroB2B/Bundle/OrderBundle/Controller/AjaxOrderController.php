<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @param Request $request
     * @return JsonResponse
     */
    public function subtotalsAction(Request $request)
    {
        $orderClass = $this->getParameter('orob2b_order.entity.order.class');
        $id = $request->get('id');
        if ($id) {
            /** @var Order $order */
            $order = $this->getDoctrine()->getManagerForClass($orderClass)->find($orderClass, $id);
        } else {
            $order = new $orderClass();
        }

        $form = $this->createForm(OrderType::NAME, $order);
        $form->submit($this->get('request'));

        $subtotals = $this->get('orob2b_order.provider.subtotals')->getSubtotals($order);
        $subtotals = $subtotals->map(
            function (Subtotal $subtotal) {
                return $subtotal->toArray();
            }
        )->toArray();

        return new JsonResponse(['subtotals' => $subtotals]);
    }

    /**
     * Get order related data
     *
     * @Route("/related-data/{accountId}", name="orob2b_order_related_data", requirements={"accountId"="\d+"})
     * @Method({"GET"})
     * @AclAncestor("orob2b_order_update")
     *
     * @ParamConverter("account", options={"id" = "accountId"})
     *
     * @param Account $account
     * @param Request $request
     * @return JsonResponse
     */
    public function getRelatedDataAction(Account $account, Request $request)
    {
        $order = new Order();
        $order->setAccount($account);

        /** @var AccountUser $accountUser */
        $accountUser = null;
        $accountUserId = $request->get('accountUserId');
        if ($accountUserId) {
            $accountUserClass = $this->getParameter('orob2b_account.entity.account_user.class');

            $accountUser = $this->getDoctrine()
                ->getManagerForClass($accountUserClass)
                ->find($accountUserClass, $accountUserId);

            if ($accountUser
                && $accountUser->getAccount()
                && $accountUser->getAccount()->getId() !== $account->getId()
            ) {
                throw new BadRequestHttpException('AccountUser must belong to Account');
            }
            $order->setAccountUser($accountUser);
        }

        $accountPaymentTerm = $this->getPaymentTermProvider()->getAccountPaymentTerm($account);
        $accountGroupPaymentTerm = null;
        if ($account->getGroup()) {
            $accountGroupPaymentTerm = $this->getPaymentTermProvider()
                ->getAccountGroupPaymentTerm($account->getGroup());
        }

        return new JsonResponse(
            [
                'billingAddress' => $this->renderForm(
                    $this->createAddressForm($order, AddressType::TYPE_BILLING)->createView()
                ),
                'shippingAddress' => $this->renderForm(
                    $this->createAddressForm($order, AddressType::TYPE_SHIPPING)->createView()
                ),
                'accountPaymentTerm' => $accountPaymentTerm ? $accountPaymentTerm->getId() : null,
                'accountGroupPaymentTerm' => $accountGroupPaymentTerm ? $accountGroupPaymentTerm->getId() : null,
            ]
        );
    }

    /**
     * @param Order $order
     * @param string $type
     * @return Form
     */
    protected function createAddressForm(Order $order, $type)
    {
        return $this->createForm(
            OrderAddressType::NAME,
            null,
            ['order' => $order, 'required' => false, 'addressType' => $type]
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
