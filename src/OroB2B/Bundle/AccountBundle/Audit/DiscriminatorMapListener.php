<?php

namespace OroB2B\Bundle\AccountBundle\Audit;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DiscriminatorMapListener
{
    /**
     * @var string[]
     */
    protected $supportedClassNames = [];

    /**
     * @param string[] $supportedClassNames
     */
    public function __construct(array $supportedClassNames = [])
    {
        $this->supportedClassNames = $supportedClassNames;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (!$metadata->isInheritanceTypeSingleTable()) {
            return;
        }

        if (!$metadata->isRootEntity()) {
            return;
        }

        $className = $metadata->getName();
        foreach ($this->supportedClassNames as $key => $supportedClassName) {
            if (is_a($supportedClassName, $className, true)) {
                $metadata->discriminatorMap[$key] = $supportedClassName;
            }
        }
    }
}
