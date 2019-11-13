<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Configures security check for customer visitors:
 * * removes ACL resource for shopping list and line item entities;
 * * removes AccessGranted validators for shopping list and line item associations.
 */
class GuestShoppingListAccessCheck implements ProcessorInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var string */
    private $associationPath;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param string                $associationPath
     */
    public function __construct(TokenStorageInterface $tokenStorage, string $associationPath)
    {
        $this->tokenStorage = $tokenStorage;
        $this->associationPath = $associationPath;
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

        /** @var EntityDefinitionConfig $definition */
        $definition = $context->getResult();
        $definition->setAclResource();

        $fieldDefinition = $definition->findFieldByPath($this->associationPath);
        if (null !== $fieldDefinition) {
            $fieldDefinition->removeFormConstraint(AccessGranted::class);
        }
    }
}
