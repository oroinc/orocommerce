<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;
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
     * @param Request $request
     * @return JsonResponse
     */
    public function getRelatedDataAction(Request $request)
    {
        $order = new Order();

        /** @var AccountUser $accountUser */
        $accountUser = null;
        /** @var Account $account */
        $account = null;
        $accountUserId = $request->get('accountUserId');
        if ($accountUserId) {
            $accountUserClass = $this->getParameter('orob2b_account.entity.account_user.class');

            $accountUser = $this->findEntity($accountUserClass, $accountUserId);
            $order->setAccountUser($accountUser);
            $account = $accountUser->getAccount();
        }

        $accountId = $request->get('accountId');
        if ($accountId && !$account) {
            $accountClass = $this->getParameter('orob2b_account.entity.account.class');
            $account = $this->findEntity($accountClass, $accountId);
        }
        $order->setAccount($account);

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

    /**
     * @param string $entityClass
     * @param int $id
     * @return object
     */
    protected function findEntity($entityClass, $id)
    {
        $entity = $this->getDoctrine()
            ->getManagerForClass($entityClass)
            ->find($entityClass, $id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $entity;
    }
}
