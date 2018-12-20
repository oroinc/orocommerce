<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the related product is granted.
 */
class RelatedItemSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var array */
    private $productPermissions;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param string[]                      $productPermissions
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, array $productPermissions)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->productPermissions = $productPermissions;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$this->authorizationChecker->isGranted('oro_related_products_edit')) {
            throw new AccessDeniedException();
        }

        foreach ($this->productPermissions as $productPermission) {
            if (!$this->authorizationChecker->isGranted($productPermission, Product::class)) {
                throw new AccessDeniedException();
            }
        }

        $context->skipGroup('security_check');
    }
}
