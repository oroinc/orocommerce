<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

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
        if (!$this->getOrderHandler()
            ->setOrderAccountUser($order)
        ) {
            throw new BadRequestHttpException('AccountUser must belong to Account');
        }

        $account = $order->getAccount();
        $accountPaymentTerm = $this->getPaymentTermProvider()
            ->getAccountPaymentTerm($account);
        $accountGroupPaymentTerm = null;
        if ($account->getGroup()) {
            $accountGroupPaymentTerm = $this->getPaymentTermProvider()
                ->getAccountGroupPaymentTerm($account->getGroup());
        }

        $orderForm = $this->createForm(OrderType::NAME, $order);

        return new JsonResponse(
            [
                'billingAddress' => $this->renderForm(
                    $orderForm->get(AddressType::TYPE_BILLING . 'Address')
                        ->createView()
                ),
                'shippingAddress' => $this->renderForm(
                    $orderForm->get(AddressType::TYPE_SHIPPING . 'Address')
                        ->createView()
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
     * @param string $entityClass
     * @param int $id
     *
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

    /**
     * @param int $accountUserId
     *
     * @return AccountUser
     */
    protected function findAccountUser($accountUserId)
    {
        $accountUser = null;

        if ($accountUserId) {
            $accountUserClass = $this->getParameter('orob2b_account.entity.account_user.class');
            /** @var AccountUser $accountUser */
            $accountUser = $this->findEntity($accountUserClass, $accountUserId);
        }

        return $accountUser;
    }

    /**
     * @param int $accountId
     * @param AccountUser|null $accountUser
     *
     * @return Account
     *
     * @throws BadRequestHttpException
     */
    protected function findAccount($accountId, AccountUser $accountUser = null)
    {
        $account = null;

        if ($accountId) {
            $accountClass = $this->getParameter('orob2b_account.entity.account.class');
            /** @var Account $account */
            $account = $this->findEntity($accountClass, $accountId);
        }

        if ($accountUser) {
            if ($account) {
                $this->validateRelation($accountUser, $account);
            } else {
                $account = $accountUser->getAccount();
            }
        }

        return $account;
    }

    /**
     * @param AccountUser $accountUser
     * @param Account $account
     *
     * @throws BadRequestHttpException
     */
    protected function validateRelation(AccountUser $accountUser, Account $account)
    {
        if ($accountUser
            && $accountUser->getAccount()
            && $accountUser->getAccount()
                ->getId() !== $account->getId()
        ) {
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
