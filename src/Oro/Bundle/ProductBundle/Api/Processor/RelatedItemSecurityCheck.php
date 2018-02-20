<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RelatedItemSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var array */
    private $productPermissions;

    /** @var array */
    private $capabilities;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param array                         $productPermissions
     * @param array                         $capabilities
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        array $productPermissions = [],
        array $capabilities = []
    ) {
        $this->authorizationChecker = $authorizationChecker;
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

        $productObject = new ObjectIdentity('entity', Product::class);

        foreach ($this->productPermissions as $productPermission) {
            if (!$this->authorizationChecker->isGranted($productPermission, $productObject)) {
                throw new AccessDeniedException();
            }
        }

        $context->skipGroup('security_check');
    }
}
