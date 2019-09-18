<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\CMSBundle\Entity\EntityListener\TextContentVariantEntityListener;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;

class TextContentVariantEntityListenerTest extends ContentAwareEntityListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getEntityListenerClass(): string
    {
        return TextContentVariantEntityListener::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return TextContentVariant::class;
    }
}
