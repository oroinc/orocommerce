<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_12_1;

use Oro\Bundle\CMSBundle\Migration\RepairAllWysiwygFieldsSchemaMigration;

/**
 * Adds additional WYSIWYG fields (_style and _properties) if they did not exist before.
 */
class CreateAdditionalWysiwygFields extends RepairAllWysiwygFieldsSchemaMigration
{
}
