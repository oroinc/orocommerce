<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /** {@inheritdoc} */
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
