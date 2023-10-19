<?php

namespace Oro\Bundle\WebCatalogBundle\Context;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Contains Content Node deletion check result
 */
class NotDeletableContentNodeResult
{
    private array $warningMessageParams = [];

    private bool $isChild = false;

    private ContentNode $referencedContendNode;

    public function getWarningMessageParams(): array
    {
        return $this->warningMessageParams;
    }

    public function setWarningMessageParams(array $warningMessageParams): void
    {
        $this->warningMessageParams = $warningMessageParams;
    }

    /**
     * @return bool
     */
    public function isChild(): bool
    {
        return $this->isChild;
    }

    public function setIsChild(): void
    {
        $this->isChild = true;
    }

    public function getReferencedContendNode(): ContentNode
    {
        return $this->referencedContendNode;
    }

    public function setReferencedContendNode(ContentNode $referencedContendNode): void
    {
        $this->referencedContendNode = $referencedContendNode;
    }
}
