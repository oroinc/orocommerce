<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;

interface ContentVariantWithEntity extends ContentVariantTypeInterface, ContentVariantEntityProviderInterface
{
}
