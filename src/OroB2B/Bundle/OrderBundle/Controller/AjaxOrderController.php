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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

        $accountUser = $this->findAccountUser($request->get('accountUserId'));
        $account = $this->findAccount($request->get('accountId'), $accountUser);

        $order->setAccount($account);
        $order->setAccountUser($accountUser);

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
            && $accountUser->getAccount()->getId() !== $account->getId()
        ) {
            throw new BadRequestHttpException('AccountUser must belong to Account');
        }
    }
}
