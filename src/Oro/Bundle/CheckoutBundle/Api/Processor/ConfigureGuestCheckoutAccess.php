<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Validator\Constraints as Assert;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes AccessGranted validation constraint for specified associations
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class ConfigureGuestCheckoutAccess implements ProcessorInterface
{
    /** @var GuestCheckoutChecker */
    private $guestCheckoutChecker;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var string[] */
    private $associationNames;

    public function __construct(
        GuestCheckoutChecker $guestCheckoutChecker,
        DoctrineHelper $doctrineHelper,
        array $associationNames
    ) {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->doctrineHelper = $doctrineHelper;
        $this->associationNames = $associationNames;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        $config = $context->getResult();
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName());
        foreach ($this->associationNames as $associationName) {
            $field = $config->findField($associationName, true);
            if (null !== $field) {
                if ($metadata->isCollectionValuedAssociation($associationName)) {
                    $field->removeFormConstraint(Assert\All::class);
                } else {
                    $field->removeFormConstraint(Assert\AccessGranted::class);
                }
            }
        }
    }
}
