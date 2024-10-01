<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_14_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Migrations\Schema\v1_14_1\Query\UpdateFieldsConfigsMigrationQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendConfigMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Renames properties:
 *  - `title` => 'alt_image_text'
 *  - `mainImage` => `extraLargeImage`
 */
class RenameImageSlideColumns implements
    Migration,
    RenameExtensionAwareInterface,
    ContainerAwareInterface,
    DataStorageExtensionAwareInterface
{
    use MigrationConstraintTrait;
    use ContainerAwareTrait;

    private RenameExtension $renameExtension;
    private DataStorageExtension $dataStorageExtension;

    #[\Override]
    public function setRenameExtension(RenameExtension $renameExtension): void
    {
        $this->renameExtension = $renameExtension;
    }

    #[\Override]
    public function setDataStorageExtension(DataStorageExtension $dataStorageExtension)
    {
        $this->dataStorageExtension = $dataStorageExtension;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($this->isRequiresConfigUpdates()) {
            /**
             * This step is required, if table oro_image_slide with columns are added in the same update runtime at v1_7
             * @see \Oro\Bundle\CMSBundle\Migrations\Schema\v1_7\OroCMSBundle
             * But all necessary entity and field configs assembled only after all migrations done.
             * So, here manually executed all required migration queries to fill all config tables with required data
             */
            $commandExecutor = $this->container->get('oro_entity_config.tools.command_executor');

            /** @see \Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration */
            $queries->addPreQuery(new UpdateEntityConfigMigrationQuery($commandExecutor));

            /** @see \Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration */
            $queries->addPreQuery(new UpdateExtendConfigMigrationQuery(
                $schema->getExtendOptions(),
                $commandExecutor,
                $this->container->getParameter('oro_entity_extend.migration.config_processor.options.path'),
            ));
            $queries->addPreQuery(
                new RefreshExtendConfigMigrationQuery(
                    $commandExecutor,
                    $this->dataStorageExtension->get('initial_entity_config_state', []),
                    $this->container->getParameter('oro_entity_extend.migration.initial_entity_config_state.path'),
                )
            );
        }

        $entityClass = ImageSlide::class;
        $table = $schema->getTable('oro_cms_image_slide');

        $fieldNames = [
            'title' => 'altImageText',
            'mainImage' => 'extraLargeImage',
        ];
        $configManager = $this->container->get('oro_entity_config.config_manager');

        foreach ($fieldNames as $oldFieldName => $newFieldName) {
            $queries->addPreQuery(
                new UpdateFieldsConfigsMigrationQuery($configManager, $entityClass, $oldFieldName, $newFieldName)
            );
        }

        // rename columns
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'title',
            'alt_image_text'
        );

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'mainimage_id',
            'extralargeimage_id'
        );
    }

    private function isRequiresConfigUpdates(): bool
    {
        /** @var Connection $connection */
        $connection = $this->container->get('doctrine')->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->select('m.version')
            ->from('oro_migrations', 'm')
            ->andWhere('m.bundle = :bundle')
            ->setParameter('bundle', 'OroCMSBundle', Types::STRING);
        $version = $qb->execute()->fetchOne();

        return !$version || version_compare($version, 'v1_7', '<');
    }
}
