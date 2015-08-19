<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;

class PaymentTermHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return boolean
     */
    public function process(PaymentTerm $paymentTerm)
    {
        $this->form->setData($paymentTerm);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess(
                    $paymentTerm,
                    $this->form->get('appendAccounts')->getData(),
                    $this->form->get('removeAccounts')->getData(),
                    $this->form->get('appendAccountGroups')->getData(),
                    $this->form->get('removeAccountGroups')->getData()
                );

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param PaymentTerm $entity
     * @param Account[] $appendAccounts
     * @param Account[] $removeAccounts
     * @param AccountGroup[] $appendAccountGroups
     * @param AccountGroup[] $removeAccountGroups
     */
    protected function onSuccess(
        PaymentTerm $entity,
        array $appendAccounts,
        array $removeAccounts,
        array $appendAccountGroups,
        array $removeAccountGroups
    ) {
        $this->manager->persist($entity);

        // first stage - remove relations to entities, used to prevent unique key conflicts
        $this->setPaymentTermToAccounts(array_merge($appendAccounts, $removeAccounts));
        $this->setPaymentTermToAccountGroups(array_merge($appendAccountGroups, $removeAccountGroups));

        $this->manager->flush();

        // second stage - set correct relations
        $this->setPaymentTermToAccounts($appendAccounts, $entity);
        $this->setPaymentTermToAccountGroups($appendAccountGroups, $entity);

        $this->manager->flush();
    }

    /**
     * @param Account[] $accounts
     * @param PaymentTerm|null $paymentTerm
     */
    protected function setPaymentTermToAccounts(array $accounts, PaymentTerm $paymentTerm = null)
    {
        $repository = $this->getPaymentTermRepository();

        foreach ($accounts as $account) {
            $repository->setPaymentTermToAccount($account, $paymentTerm);
        }
    }

    /**
     * @param AccountGroup[] $accountGroups
     * @param PaymentTerm|null $paymentTerm
     */
    protected function setPaymentTermToAccountGroups(array $accountGroups, PaymentTerm $paymentTerm = null)
    {
        $repository = $this->getPaymentTermRepository();

        foreach ($accountGroups as $accountGroup) {
            $repository->setPaymentTermToAccountGroup($accountGroup, $paymentTerm);
        }
    }

    /**
     * @return PaymentTermRepository
     */
    protected function getPaymentTermRepository()
    {
        return $this->manager->getRepository('OroB2BPaymentBundle:PaymentTerm');
    }
}
