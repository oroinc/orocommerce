<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_14_1\Query;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Changes config names and translations for [title, mainImage] fields.
 */
class UpdateFieldsConfigsMigrationQuery implements MigrationQuery
{
    use UpdateExtendRelationTrait;

    public function __construct(
        private ConfigManager $configManager,
        private string $entityClass,
        private string $oldFieldName,
        private string $newFieldName
    ) {
    }

    public function getDescription(): string
    {
        return sprintf(
            'Changes field "%s" config name and translations.',
            $this->oldFieldName
        );
    }

    public function execute(LoggerInterface $logger): void
    {
        $this->configManager->flush();
        $this->configManager->clearCache();

        if ($this->configManager->hasConfig($this->entityClass, $this->oldFieldName)) {
            $this->fixFieldLabels();
            switch ($this->oldFieldName) {
                case 'mainImage':
                    $this->fixFieldRelation();
                    $this->migrateConfig(
                        $this->configManager,
                        $this->entityClass,
                        File::class,
                        $this->oldFieldName,
                        $this->newFieldName,
                        RelationType::MANY_TO_ONE
                    );
                    break;
                case 'title':
                    $this->configManager->changeFieldName(
                        $this->entityClass,
                        $this->oldFieldName,
                        $this->newFieldName
                    );
                    break;
            }
        }
    }

    protected function fixFieldRelation(): void
    {
        $config = $this->configManager->getFieldConfig(
            'extend',
            $this->entityClass,
            $this->oldFieldName
        );
        $extendProp = 'relation_key';
        if ($config->has($extendProp)) {
            $value = $config->get($extendProp);
            $config->set($extendProp, str_replace($this->oldFieldName, $this->newFieldName, $value));
            $this->configManager->persist($config);
            $this->configManager->flush();
        }
    }

    protected function fixFieldLabels(): void
    {
        $config = $this->configManager->getFieldConfig(
            'entity',
            $this->entityClass,
            $this->oldFieldName
        );
        $entityProps = ['label', 'description', 'tooltip'];
        $hasChanges = false;
        foreach ($entityProps as $entityProp) {
            if ($config->has($entityProp)) {
                $expected = ConfigHelper::getTranslationKey(
                    'entity',
                    $entityProp,
                    ImageSlide::class,
                    $this->newFieldName
                );
                if ($config->get($entityProp) !== $expected) {
                    $config->set($entityProp, $expected);
                    $hasChanges = true;
                }
            }
        }
        if ($hasChanges) {
            $this->configManager->persist($config);
            $this->configManager->flush();
        }
    }
}
