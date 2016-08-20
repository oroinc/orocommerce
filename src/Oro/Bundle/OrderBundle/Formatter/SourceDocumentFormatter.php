<?php

namespace Oro\Bundle\OrderBundle\Formatter;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;

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
     * @param string $sourceEntityClass
     * @param integer $sourceEntityId
     * @param string $sourceEntityIdentifier
     *
     * @return string
     */
    public function format($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
    {
        $class = '';
        $identifier = '';
        if (!empty($sourceEntityClass)) {
            if (!empty($sourceEntityIdentifier)) {
                $identifier = $sourceEntityIdentifier;
            }

            if (empty($identifier) && $sourceEntityId > 0) {
                $identifier = $sourceEntityId;
            }

            $class = $this->chainEntityClassNameProvider->getEntityClassName($sourceEntityClass);
        }

        return trim(sprintf('%s %s', $class, $identifier));
    }
}
