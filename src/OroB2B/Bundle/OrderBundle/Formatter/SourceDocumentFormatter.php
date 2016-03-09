<?php

namespace OroB2B\Bundle\OrderBundle\Formatter;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;

use OroB2B\Bundle\OrderBundle\Provider\IdentifierAwareInterface;

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
     * @param $entity
     *
     * @return string
     */
    public function format($entity)
    {
        $identifier = '';
        if ($entity instanceof IdentifierAwareInterface) {
            $identifier = $entity->getIdentifier();
        }

        $fullClass = ClassUtils::getClass($entity);
        $class = $this->chainEntityClassNameProvider->getEntityClassName($fullClass);

        return trim(sprintf('%s %s', $class, $identifier));
    }
}
