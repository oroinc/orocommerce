<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Configures security check for customer visitors:
 * * removes ACL resource for shopping list and line item entities
 * * removes AccessGranted validators for shopping list and line item associations
 */
class ConfigureGuestShoppingListAccess implements ProcessorInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var string */
    private $associationName;

    public function __construct(TokenStorageInterface $tokenStorage, string $associationName)
    {
        $this->tokenStorage = $tokenStorage;
        $this->associationName = $associationName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if (!$this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            return;
        }

        $definition = $context->getResult();
        $definition->setAclResource();

        $field = $definition->findField($this->associationName, true);
        if (null !== $field) {
            $field->removeFormConstraint(AccessGranted::class);
        }
    }
}
