<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSEOBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // product meta keywords
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_1cf73d3121a159ae6e1a29'));

        // product meta descriptions
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_1cf73d3121a159ae2725f3'));

        // CMS page keywords
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_b438191e21a159ae6e1a29'));

        // CMS page descriptions
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_b438191e21a159ae2725f3'));

        // category meta keywords
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_ff3a7b9721a159ae6e1a29'));

        // category meta descriptions
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_ff3a7b9721a159ae2725f3'));
    }
}
