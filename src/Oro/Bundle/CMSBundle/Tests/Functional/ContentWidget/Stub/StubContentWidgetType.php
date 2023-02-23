<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ContentWidget\Stub;

use Oro\Bundle\CMSBundle\ContentWidget\AbstractContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Twig\Environment;

class StubContentWidgetType extends AbstractContentWidgetType
{
    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'stub';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'stub';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }
}
