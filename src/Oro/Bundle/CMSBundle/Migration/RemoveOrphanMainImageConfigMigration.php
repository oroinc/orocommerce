<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Aims to clean up and fix up the entity config for field `mainImage`
 */
class RemoveOrphanMainImageConfigMigration implements Migration
{
    private ConfigManager $configManager;
    private ManagerRegistry $registry;

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $registry
    ) {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $sql = <<<QUERY
            DELETE FROM oro_entity_config_field 
            WHERE field_name = :fieldName AND entity_id IN (
                SELECT id FROM oro_entity_config WHERE class_name = :className
            );
        QUERY;

        $params = [
            'fieldName' => 'mainImage',
            'className' => ImageSlide::class,
        ];

        $types = [
            'fieldName' => Types::STRING,
            'className' => Types::STRING,
        ];
        $this->registry->getConnection()->executeStatement($sql, $params, $types);

        $config = $this->configManager->getEntityConfig('extend', ImageSlide::class);
        $values = $config->getValues();
        unset($values['schema']['relation']['mainImage']);
        $relationKey = sprintf(
            '%s|%s|%s|%s',
            'manyToOne',
            ImageSlide::class,
            File::class,
            'mainImage'
        );
        unset($values['relation'][$relationKey]);
        $config->setValues($values);
        $this->configManager->persist($config);
        $this->configManager->flush();
        $this->configManager->clearCache();

        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $cacheDriver = $em->getConfiguration()->getMetadataCache();
        if ($cacheDriver) {
            $cacheDriver->clear();
        }
        $em->clear();
    }
}
