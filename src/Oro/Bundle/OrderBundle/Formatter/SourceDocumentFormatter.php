<?php

namespace Oro\Bundle\OrderBundle\Formatter;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;

/**
 * This formatter is responsible for order`s source entity displayed name formatting
 */
class SourceDocumentFormatter
{
    /**
     * @var ChainEntityClassNameProvider
     */
    protected $chainEntityClassNameProvider;

    /**
     * @param ChainEntityClassNameProvider $chainEntityClassNameProvider
     */
    public function __construct(ChainEntityClassNameProvider $chainEntityClassNameProvider)
    {
        $this->chainEntityClassNameProvider = $chainEntityClassNameProvider;
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

        $class = $this->chainEntityClassNameProvider->getEntityClassName($sourceEntityClass);

        if ($sourceEntityIdentifier) {
            return trim(sprintf('%s "%s"', $class, $sourceEntityIdentifier));
        }
        return trim(sprintf('%s %s', $class, $sourceEntityId));
    }
}
