<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_10;

use Oro\Bundle\CMSBundle\Migration\RepairAllWysiwygFieldsSchemaMigration;

/**
 * Adds additional WYSIWYG fields (_style and _properties) if they did not exist during the platform update.
 */
class CreateAdditionalWysiwygFields extends RepairAllWysiwygFieldsSchemaMigration
{
}
