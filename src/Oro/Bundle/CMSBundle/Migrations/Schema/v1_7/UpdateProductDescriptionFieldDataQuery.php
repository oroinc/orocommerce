<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Oro\Bundle\CMSBundle\Migration\MigrateLocalizedFallbackValueWysiwygQuery;
use Psr\Log\LoggerInterface;

class UpdateProductDescriptionFieldDataQuery extends MigrateLocalizedFallbackValueWysiwygQuery
{
    /**
     * {@inheritdoc}
     */
    protected function getLocalizedValueIds(LoggerInterface $logger): array
    {
        $sql = 'SELECT flv.id AS id
            FROM oro_fallback_localization_val AS flv
            INNER JOIN oro_product_description AS pd ON pd.localized_value_id = flv.id
            INNER JOIN oro_product AS p ON p.id = pd.description_id
            WHERE flv.wysiwyg IS NULL AND flv.text IS NOT NULL';

        $this->logQuery($logger, $sql);

        return array_column($this->connection->fetchAll($sql), 'id');
    }
}
