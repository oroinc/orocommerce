<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Driver\Statement;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class CopyLocalizationReferencesToConfigQuery extends ParametrizedMigrationQuery
{
    /** @var Statement */
    private $enabledLocalizationsStatement;

    /** @var Statement */
    private $defaultLocalizationStatement;

    public function execute(LoggerInterface $logger)
    {

        $result = $this->connection->fetchAll('SELECT website_id, localization_id FROM orob2b_websites_localizations');

        $relations = [];
        foreach ($result as $localizationRel) {
            $relations[$localizationRel['website_id']][] = $localizationRel['localization_id'];
        }

        foreach ($relations as $websiteId => $localizationIds) {
            $this->connection->insert(
                'oro_config',
                [
                    'entity' => 'website',
                    'record_id' => $websiteId
                ],
                [
                    'string',
                    'integer'
                ]
            );

            $this->prepareEnabledLocalizationsInsertStatement($websiteId, $localizationIds)->execute();
            $this->prepareDefaultLocalizationStatement($websiteId, reset($localizationIds)); //take a first one
        }
    }

    protected function prepareEnabledLocalizationsInsertStatement($websiteId, array $localizationIds)
    {
        if (null === $this->enabledLocalizationsStatement) {
            $stmt = $this->connection->prepare(
                'INSERT INTO oro_config_value 
                (config_id, name, section, object_value, array_value, type, created_at, updated_at) 
                VALUES (
                  (SELECT id from oro_config WHERE entity = :entityName AND record_id = :websiteId LIMIT 1),
                  :fieldName,
                  :section,
                  :objectValue,
                  :arrayValue,
                  :type,
                  :createdAt,
                  :updatedAt
                )'
            );

            $stmt->bindValue(':entityName', 'website', 'string');
            $stmt->bindValue(':fieldName', Configuration::ENABLED_LOCALIZATIONS, 'string');
            $stmt->bindValue(':section', 'oro_locale', 'string');
            $stmt->bindValue(':objectValue', null, 'object');
            $stmt->bindValue(':type', 'array', 'string');
            $now = new \DateTime();
            $stmt->bindValue(':createdAt', $now, 'datetime');
            $stmt->bindValue(':updatedAt', $now, 'datetime');

            $this->enabledLocalizationsStatement = $stmt;
        }

        $this->enabledLocalizationsStatement->bindValue(':websiteId', $websiteId, 'integer');
        $this->enabledLocalizationsStatement->bindValue(':arrayValue', $localizationIds, 'array');

        return $this->enabledLocalizationsStatement;
    }

    protected function prepareDefaultLocalizationStatement($websiteId, $defaultLocalizationId)
    {
        if (null === $this->defaultLocalizationStatement) {
            $stmt = $this->connection->prepare(
                'INSERT INTO oro_config_value 
                (config_id, name, section, text_value, object_value, array_value, type, created_at, updated_at) 
                VALUES (
                  (SELECT id from oro_config WHERE entity = :entityName AND record_id = :websiteId LIMIT 1),
                  :fieldName,
                  :section,
                  :textValue,
                  :objectValue,
                  :arrayValue,
                  :type,
                  :createdAt,
                  :updatedAt
                )'
            );

            $stmt->bindValue(':entityName', 'website', 'string');
            $stmt->bindValue(':fieldName', Configuration::DEFAULT_LOCALIZATION, 'string');
            $stmt->bindValue(':section', 'oro_locale', 'string');
            $stmt->bindValue(':objectValue', null, 'object');
            $stmt->bindValue(':arrayValue', null, 'array');
            $stmt->bindValue(':type', 'scalar', 'string');
            $now = new \DateTime();
            $stmt->bindValue(':createdAt', $now, 'datetime');
            $stmt->bindValue(':updatedAt', $now, 'datetime');
            $this->defaultLocalizationStatement = $stmt;
        }

        $this->defaultLocalizationStatement->bindValue(':websiteId', $websiteId, 'integer');
        $this->defaultLocalizationStatement->bindValue(':textValue', $defaultLocalizationId, 'text');

        return $this->defaultLocalizationStatement;
    }

    public function getDescription()
    {
        return 'Copy websites localization relation to system configuration';
    }
}
