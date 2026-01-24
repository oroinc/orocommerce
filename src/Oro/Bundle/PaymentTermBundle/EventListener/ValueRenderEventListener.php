<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles rendering of payment term values in entity extended fields.
 *
 * This listener intercepts value render events for customer entities and displays the associated payment term.
 * If a customer has a direct payment term assignment, it displays that term. Otherwise, it checks the customer's group
 * for a payment term and displays it with an indication that it is inherited from the customer group.
 */
class ValueRenderEventListener
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var RouterInterface */
    private $router;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    public function __construct(
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function beforeValueRender(ValueRenderEvent $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Customer) {
            return;
        }

        $fieldName = $event->getFieldConfigId()->getFieldName();
        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames(ClassUtils::getClass($entity));
        if (!in_array($fieldName, $associationNames, true)) {
            return;
        }

        $customerPaymentTerm = $this->paymentTermAssociationProvider->getPaymentTerm($entity, $fieldName);
        if ($customerPaymentTerm) {
            $event->setFieldViewValue(
                ['title' => $customerPaymentTerm->getLabel(), 'link' => $this->getLink($customerPaymentTerm)]
            );

            return;
        }

        if (!$entity->getGroup()) {
            return;
        }

        $customerGroupAssociationNames = $this->paymentTermAssociationProvider->getAssociationNames(
            ClassUtils::getClass($entity->getGroup())
        );

        foreach ($customerGroupAssociationNames as $customerGroupAssociationName) {
            $customerGroupPaymentTerm = $this->paymentTermAssociationProvider->getPaymentTerm(
                $entity->getGroup(),
                $customerGroupAssociationName
            );

            if ($customerGroupPaymentTerm) {
                $event->setFieldViewValue(
                    [
                        'title' => $this->translator->trans(
                            'oro.paymentterm.customer.customer_group_defined',
                            [
                                '{{ payment_term }}' => $customerGroupPaymentTerm->getLabel(),
                            ]
                        ),
                        'link' => $this->getLink($customerGroupPaymentTerm),
                    ]
                );

                return;
            }
        }
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    protected function getLink(PaymentTerm $paymentTerm)
    {
        return $this->router->generate('oro_payment_term_view', ['id' => $paymentTerm->getId()]);
    }
}
