<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the related product is granted.
 */
class ValidateRelatedItemAccess implements ProcessorInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    /** @var string[] */
    private array $productPermissions;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, array $productPermissions)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->productPermissions = $productPermissions;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        if (!$this->authorizationChecker->isGranted('oro_related_products_edit')) {
            throw new AccessDeniedException('No access to change related products.');
        }

        foreach ($this->productPermissions as $productPermission) {
            $identityString = ObjectIdentityHelper::encodeIdentityString(
                EntityAclExtension::NAME,
                Product::class
            );

            if (!$this->authorizationChecker->isGranted($productPermission, $identityString)) {
                throw new AccessDeniedException(sprintf(
                    'No access by "%s" permission to products.',
                    $productPermission
                ));
            }
        }

        $context->skipGroup(ApiActionGroup::SECURITY_CHECK);
    }
}
