<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "loading" and "fetch_priority" columns to the ImageSlide entity table
 */
class AddImageSlideLoadingOptions implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_cms_image_slide');

        if (!$table->hasColumn('loading')) {
            $table->addColumn(
                'loading',
                'string',
                [
                    'length' => 10,
                    'default' => ImageSlide::LOADING_LAZY,
                ]
            );
        }

        if (!$table->hasColumn('fetch_priority')) {
            $table->addColumn(
                'fetch_priority',
                'string',
                [
                    'length' => 10,
                    'default' => ImageSlide::FETCH_PRIORITY_AUTO,
                ]
            );
        }
    }
}
