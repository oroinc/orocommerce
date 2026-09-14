<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\RedirectBundle\Generator\BatchCanonicalUrlGeneratorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of the "canonicalUrl" field for a sluggable entity.
 */
class ComputeCanonicalUrlField implements ProcessorInterface
{
    private const string FIELD_NAME = 'canonicalUrl';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly BatchCanonicalUrlGeneratorInterface $canonicalUrlGenerator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (!$context->isFieldRequestedForCollection(self::FIELD_NAME, $data)) {
            return;
        }

        $ownerEntityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        $ownerIdFieldName = $context->getResultFieldName(
            $this->doctrineHelper->getSingleEntityIdentifierFieldName($ownerEntityClass)
        );

        // the localization and the website are left to the generator, so that the resolved values
        // are the ones the storefront uses, including the use_localized_canonical option
        $urls = $this->canonicalUrlGenerator->getUrls(
            $ownerEntityClass,
            $context->getIdentifierValues($data, $ownerIdFieldName)
        );

        foreach ($data as $key => $item) {
            $data[$key][self::FIELD_NAME] = $urls[$item[$ownerIdFieldName]] ?? null;
        }

        $context->setData($data);
    }
}
