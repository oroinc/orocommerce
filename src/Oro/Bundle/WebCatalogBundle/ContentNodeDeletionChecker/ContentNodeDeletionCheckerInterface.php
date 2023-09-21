<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeDeletionChecker;

use Oro\Bundle\WebCatalogBundle\Context\NotDeletableContentNodeResult;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Used to determine whether a content node is referenced somewhere
 */
interface ContentNodeDeletionCheckerInterface
{
    public function check(ContentNode $contentNode): ?NotDeletableContentNodeResult;
}
