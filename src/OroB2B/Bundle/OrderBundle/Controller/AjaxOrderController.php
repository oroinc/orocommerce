<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\OrderRequestHandler;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class AjaxOrderController extends AbstractAjaxOrderController
{
    /**
     * @Route("/subtotals", name="orob2b_order_subtotals")
     * @Method({"POST"})
     * @AclAncestor("orob2b_order_update")
     *
     * {@inheritdoc}
     */
    public function subtotalsAction(Request $request)
    {
        return parent::subtotalsAction($request);
    }

    /**
     * Get order related data
     *
     * @Route("/related-data", name="orob2b_order_related_data")
     * @Method({"GET"})
     * @AclAncestor("orob2b_order_update")
     *
     * @return JsonResponse
     */
    public function getRelatedDataAction()
    {
        $order = new Order();
        $account = $this->getOrderHandler()->getAccount();
        $accountUser = $this->getOrderHandler()->getAccountUser();

        if ($account && $accountUser) {
            $this->validateRelation($accountUser, $account);
        }

        $order->setAccount($account);
        $order->setAccountUser($accountUser);

        $accountPaymentTerm = $this->getPaymentTermProvider()->getAccountPaymentTerm($account);
        $accountGroupPaymentTerm = null;
        if ($account->getGroup()) {
            $accountGroupPaymentTerm = $this->getPaymentTermProvider()
                ->getAccountGroupPaymentTerm($account->getGroup());
        }

        $orderForm = $this->createForm(OrderType::NAME, $order);

        return new JsonResponse(
            [
                'billingAddress' => $this->renderForm(
                    $orderForm->get(AddressType::TYPE_BILLING . 'Address')->createView()
                ),
                'shippingAddress' => $this->renderForm(
                    $orderForm->get(AddressType::TYPE_SHIPPING . 'Address')->createView()
                ),
                'accountPaymentTerm' => $accountPaymentTerm ? $accountPaymentTerm->getId() : null,
                'accountGroupPaymentTerm' => $accountGroupPaymentTerm ? $accountGroupPaymentTerm->getId() : null,
            ]
        );
    }

    /**
     * @param FormView $formView
     *
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

    /**
     * @param AccountUser $accountUser
     * @param Account $account
     *
     * @throws BadRequestHttpException
     */
    protected function validateRelation(AccountUser $accountUser, Account $account)
    {
        if ($accountUser && $accountUser->getAccount() && $accountUser->getAccount()->getId() !== $account->getId()) {
            throw new BadRequestHttpException('AccountUser must belong to Account');
        }
    }

    /**
     * @return OrderRequestHandler
     */
    protected function getOrderHandler()
    {
        return $this->get('orob2b_order.model.order_request_handler');

    }
}
