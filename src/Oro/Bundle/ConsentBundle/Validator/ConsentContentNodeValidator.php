<?php

namespace Oro\Bundle\ConsentBundle\Validator;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProviderInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Psr\Log\LoggerInterface;

/**
 * Validates that Content Node has ResolvedContentVariant with the correct type
 */
class ConsentContentNodeValidator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConsentContextProviderInterface
     */
    private $contextProvider;

    /**
     * @var ContentNodeTreeResolverInterface
     */
    private $contentNodeTreeResolver;

    /**
     * @param LoggerInterface $logger
     * @param ConsentContextProviderInterface $contextProvider
     * @param ContentNodeTreeResolverInterface $contentNodeTreeResolver
     */
    public function __construct(
        LoggerInterface $logger,
        ConsentContextProviderInterface $contextProvider,
        ContentNodeTreeResolverInterface $contentNodeTreeResolver
    ) {
        $this->logger = $logger;
        $this->contextProvider = $contextProvider;
        $this->contentNodeTreeResolver = $contentNodeTreeResolver;
    }

    /**
     * @param ContentNode $contentNode
     * @param Consent     $consent
     * @param bool        $logValidationErrors
     *
     * @return bool
     */
    public function isValid(ContentNode $contentNode, Consent $consent, $logValidationErrors = true)
    {
        $scope = $this->contextProvider->getScope();
        if ($scope instanceof Scope) {
            $resolvedNode = $this->contentNodeTreeResolver->getResolvedContentNode($contentNode, $scope);

            if (!$resolvedNode instanceof ResolvedContentNode) {
                if ($logValidationErrors) {
                    $this->logger->error(
                        sprintf(
                            "Failed to resolve 'ContentNode' in Consent with id '%d' with Scope with id '%d'!",
                            $consent->getId(),
                            $scope->getId()
                        )
                    );
                }

                return false;
            }

            $contentVariantType = $resolvedNode->getResolvedContentVariant()->getType();
            if ($contentVariantType === CmsPageContentVariantType::TYPE) {
                return true;
            } else {
                if ($logValidationErrors) {
                    $this->logger->error(
                        sprintf(
                            "Expected 'ContentVariant' with type 'cms_page' but got '%s' ".
                            "in Consent with id '%d' with 'Scope' with id '%d'!",
                            $contentVariantType,
                            $consent->getId(),
                            $scope->getId()
                        )
                    );
                }

                return false;
            }
        }

        return false;
    }
}
