<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_3;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class DropMetaTitleFieldsQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    private string $className;
    private ExtendDbIdentifierNameGenerator $nameGenerator;

    public function __construct(string $className, ExtendDbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
        $this->className = $className;
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $query = sprintf(
            'DELETE FROM oro_fallback_localization_val WHERE id IN (SELECT localizedfallbackvalue_id FROM %s);',
            $this->nameGenerator->generateManyToManyJoinTableName(
                $this->className,
                'metaTitles',
                LocalizedFallbackValue::class
            )
        );

        $logger->info($query);

        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
