<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ContentWidget\Stub;

use Oro\Bundle\CMSBundle\ContentWidget\AbstractContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Twig\Environment;

class StubContentWidgetType extends AbstractContentWidgetType
{
    #[\Override]
    public static function getName(): string
    {
        return 'stub';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'stub';
    }

    #[\Override]
    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }
}
