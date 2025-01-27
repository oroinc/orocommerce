<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\AvailableAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Loads available billing or shipping addresses for Checkout entity.
 */
class LoadAvailableAddressesSubresource implements ProcessorInterface
{
    private const string FORMAT_ADDRESS_SEPARATOR = ', ';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly OrderAddressManager $orderAddressManager,
        private readonly TranslatorInterface $translator,
        private readonly AddressFormatter $addressFormatter,
        private readonly string $addressType
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var GetSubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult($this->getAvailableAddresses($context->getParentId()));
    }

    private function getAvailableAddresses(int $checkoutId): array
    {
        $checkout = $this->doctrineHelper->getEntity(Checkout::class, $checkoutId);
        if (null === $checkout
            || $checkout->isDeleted()
            || !$this->authorizationChecker->isGranted(BasicPermission::VIEW, $checkout)
        ) {
            return [];
        }

        $availableAddresses = [];
        $groupedAddresses = $this->orderAddressManager->getGroupedAddresses(
            $checkout,
            $this->addressType,
            'oro.checkout.'
        )->toArray();
        foreach ($groupedAddresses as $groupLabel => $addresses) {
            foreach ($addresses as $addressIdentifier => $address) {
                $availableAddresses[] = new AvailableAddress(
                    $addressIdentifier,
                    $address,
                    $this->translator->trans($groupLabel),
                    $this->addressFormatter->format($address, null, self::FORMAT_ADDRESS_SEPARATOR)
                );
            }
        }

        return $availableAddresses;
    }
}
