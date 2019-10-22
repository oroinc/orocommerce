# WYSIWYG Field

[WYSIWYG](https://en.wikipedia.org/wiki/WYSIWYG) field provides an abstraction layer for integration with any editor, out-of-the-box WYSIWYG is integrated with [GrapesJS](https://grapesjs.com/docs/) editor.
An administrator can add WYSIWYG field to any entity in the Entity Management. Landing Page and [Content Blocks](./reference/content_blocks.md) in content section have WYSIWYG by default.

## Structure

WYSIWYG field consists of three parts:
 * `content` - field to save HTML;
 * `styles` - field to save CSS;
 * `properties` - field to save JSON;
 
For example, if you add a field with type WYSIWYG to an entity and provide a`description`, fields `description_styles` and `description_properties` are created automatically.

## How to add WYSIWYG field

```php
<?php

namespace Acme\Bundle\DemoBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddContentField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('acme_demo_node');
        $table->addColumn('content', 'wysiwyg', ['notnull' => false]);
        $table->addColumn('content_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('content_properties', 'wysiwyg_properties', ['notnull' => false]);
    }
}

```

## How to change TextArea field to WYSIWYG field

```php
<?php

namespace Acme\Bundle\DemoBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeContentField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('acme_demo_node');
        $table->changeColumn(
            'content',
            ['type' => WYSIWYGType::getType('wysiwyg'), 'comment' => '(DC2Type:wysiwyg)']
        );
        $table->addColumn('content_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('content_properties', 'wysiwyg_properties', ['notnull' => false]);
    }
}

```

## References

* [WYSIWYG Wikipedia](https://en.wikipedia.org/wiki/WYSIWYG)
* [GrapesJS Doc](https://grapesjs.com/docs/)
