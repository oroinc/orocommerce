<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ValueRenderEventListener
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var RouterInterface */
    private $router;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /**
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     */
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

        $accountPaymentTerm = $this->paymentTermAssociationProvider->getPaymentTerm($entity, $fieldName);
        if ($accountPaymentTerm) {
            $event->setFieldViewValue(
                ['title' => $accountPaymentTerm->getLabel(), 'link' => $this->getLink($accountPaymentTerm)]
            );

            return;
        }

        if (!$entity->getGroup()) {
            return;
        }

        $accountGroupAssociationNames = $this->paymentTermAssociationProvider->getAssociationNames(
            ClassUtils::getClass($entity->getGroup())
        );

        foreach ($accountGroupAssociationNames as $accountGroupAssociationName) {
            $accountGroupPaymentTerm = $this->paymentTermAssociationProvider->getPaymentTerm(
                $entity->getGroup(),
                $accountGroupAssociationName
            );

            if ($accountGroupPaymentTerm) {
                $event->setFieldViewValue(
                    [
                        'title' => $this->translator->trans(
                            'oro.paymentterm.account.account_group_defined',
                            [
                                '{{ payment_term }}' => $accountGroupPaymentTerm->getLabel(),
                            ]
                        ),
                        'link' => $this->getLink($accountGroupPaymentTerm),
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
