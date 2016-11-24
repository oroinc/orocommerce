<?php

namespace Oro\Bundle\FrontendBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\MappingException;
use Oro\Bundle\FrontendBundle\CacheWarmer\ClassMigration;

/**
 * TODO: remove this listener after stable release
 */
class LoadClassMetadataEventListener
{
    /**
     * @var ClassMigration
     */
    private $classMigration;

    /**
     * @param ClassMigration $classMigration
     */
    public function __construct(ClassMigration $classMigration)
    {
        $this->classMigration = $classMigration;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $eventArgs->getClassMetadata();
        try {
            $classMetadata->validateAssociations();
        } catch (MappingException $e) {
            foreach ($classMetadata->associationMappings as $name => $associationMapping) {
                if (array_key_exists('targetEntity', $associationMapping)) {
                    $classMetadata->associationMappings[$name]['targetEntity'] =
                       $this->classMigration->replaceStringValues($associationMapping['targetEntity']);
                }
            }
        }
    }
}
