<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies updating address for read-only Checkout entity.
 */
class DenyUpdateAddressForReadonlyCheckout implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        /** @var CheckoutAddress $address */
        $address = $context->getResult();
        /** @var Checkout|null $checkout */
        $checkout = $this->doctrineHelper->createQueryBuilder(Checkout::class, 'c')
            ->where('c.billingAddress = :addressId OR c.shippingAddress = :addressId')
            ->setParameter('addressId', $address->getId())
            ->getQuery()
            ->getOneOrNullResult();
        if (null === $checkout) {
            return;
        }

        if ($checkout->isCompleted()) {
            throw new AccessDeniedException('The completed checkout cannot be changed.');
        }
        if ($checkout->isPaymentInProgress()) {
            throw new AccessDeniedException('The checkout cannot be changed as the payment is being processed.');
        }
    }
}
