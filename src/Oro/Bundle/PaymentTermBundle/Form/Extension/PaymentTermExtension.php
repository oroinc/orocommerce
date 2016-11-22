<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\AccountOwnerAwareInterface;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class PaymentTermExtension extends AbstractTypeExtension
{
    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var bool[] */
    protected $replacedFields = [];

    /**
     * @param PaymentTermProvider $paymentTermProvider
     */
    public function __construct(PaymentTermProvider $paymentTermProvider)
    {
        $this->paymentTermProvider = $paymentTermProvider;
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $parent = $form->getParent();
                if (!$parent) {
                    return;
                }

                if (array_key_exists($this->getKey($parent, $form), $this->replacedFields)) {
                    return;
                }

                $data = $parent->getData();
                if (!$data) {
                    return;
                }

                if (!$data instanceof AccountOwnerAwareInterface) {
                    return;
                }

                $this->replacedFields[$this->getKey($parent, $form)] = true;
                FormUtils::replaceField($parent, $form->getName(), $this->getPaymentTermOptions($data));
            }
        );
    }

    /**
     * @param FormInterface $parent
     * @param FormInterface $child
     * @return string
     */
    protected function getKey(FormInterface $parent, FormInterface $child)
    {
        return implode('+', [$parent->getName(), $child->getName()]);
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException If extendedType not set
     */
    public function getExtendedType()
    {
        return PaymentTermSelectType::NAME;
    }

    /**
     * @param AccountOwnerAwareInterface $data
     * @return array
     */
    protected function getPaymentTermOptions(AccountOwnerAwareInterface $data)
    {
        $options = [
            'attr' => [
                'data-account-payment-term' => $this->getAccountPaymentTermId($data),
                'data-account-group-payment-term' => $this->getAccountGroupPaymentTermId($data),
            ],
        ];

        return $options;
    }

    /**
     * @param AccountOwnerAwareInterface $accountOwnerAware
     * @return int|null
     */
    protected function getAccountPaymentTermId(AccountOwnerAwareInterface $accountOwnerAware)
    {
        $paymentTerm = $this->paymentTermProvider->getAccountPaymentTermByOwner($accountOwnerAware);

        return $paymentTerm ? $paymentTerm->getId() : null;
    }

    /**
     * @param AccountOwnerAwareInterface $accountOwnerAware
     * @return int|null
     */
    protected function getAccountGroupPaymentTermId(AccountOwnerAwareInterface $accountOwnerAware)
    {
        $paymentTerm = $this->paymentTermProvider->getAccountGroupPaymentTermByOwner($accountOwnerAware);

        return $paymentTerm ? $paymentTerm->getId() : null;
    }
}
