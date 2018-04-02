<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the related product is granted.
 */
class RelatedItemSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var AclGroupProviderInterface */
    private $aclGroupProvider;

    /** @var array */
    private $productPermissions;

    /** @var array */
    private $capabilities;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AclGroupProviderInterface     $aclGroupProvider
     * @param array                         $productPermissions
     * @param array                         $capabilities
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AclGroupProviderInterface $aclGroupProvider,
        array $productPermissions = [],
        array $capabilities = []
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->aclGroupProvider = $aclGroupProvider;
        $this->productPermissions = $productPermissions;
        $this->capabilities = $capabilities;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        foreach ($this->capabilities as $capability) {
            if (!$this->authorizationChecker->isGranted($capability)) {
                throw new AccessDeniedException();
            }
        }

        $productObject = new ObjectIdentity(
            'entity',
            ObjectIdentityHelper::buildType(Product::class, $this->aclGroupProvider->getGroup())
        );
        foreach ($this->productPermissions as $productPermission) {
            if (!$this->authorizationChecker->isGranted($productPermission, $productObject)) {
                throw new AccessDeniedException();
            }
        }

        $context->skipGroup('security_check');
    }
}
