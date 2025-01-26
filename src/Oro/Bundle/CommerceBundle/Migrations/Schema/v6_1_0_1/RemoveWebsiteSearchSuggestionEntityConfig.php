<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Migrations\Schema\v6_1_0_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveWebsiteSearchSuggestionEntityConfig implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $classNames = [
            'Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion',
            'Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion'
        ];
        foreach ($classNames as $className) {
            if (!class_exists($className, false)) {
                $queries->addQuery(new RemoveTableQuery($className));
            }
        }
    }
}
