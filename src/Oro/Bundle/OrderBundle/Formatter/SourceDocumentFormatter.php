<?php

namespace Oro\Bundle\OrderBundle\Formatter;

use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

/**
 * This formatter is responsible for order`s source entity displayed name formatting
 */
class SourceDocumentFormatter
{
    /** @var EntityClassNameProviderInterface */
    private $entityClassNameProvider;

    public function __construct(EntityClassNameProviderInterface $entityClassNameProvider)
    {
        $this->entityClassNameProvider = $entityClassNameProvider;
    }

    /**
     * @param string|null $sourceEntityClass
     * @param integer|null $sourceEntityId
     * @param string|null $sourceEntityIdentifier
     *
     * @return string
     */
    public function format($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
    {
        if (!$sourceEntityClass) {
            return '';
        }

        $class = $this->entityClassNameProvider->getEntityClassName($sourceEntityClass);

        if ($sourceEntityIdentifier) {
            return trim(sprintf('%s "%s"', $class, $sourceEntityIdentifier));
        }

        return trim(sprintf('%s %s', $class, $sourceEntityId));
    }
}
