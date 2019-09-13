<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ContentWidget\Stub;

use Oro\Bundle\CMSBundle\ContentWidget\AbstractContentWidgetType;

class StubContentWidgetType extends AbstractContentWidgetType
{
    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'stub';
    }
}
