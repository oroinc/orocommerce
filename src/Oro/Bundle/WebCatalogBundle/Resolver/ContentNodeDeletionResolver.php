<?php

namespace Oro\Bundle\WebCatalogBundle\Resolver;

use Oro\Bundle\WebCatalogBundle\ContentNodeDeletionChecker\ContentNodeDeletionCheckerInterface;
use Oro\Bundle\WebCatalogBundle\Context\NotDeletableContentNodeResult;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Determines whether can content node be deleted by checking references in its tree structure
 */
class ContentNodeDeletionResolver
{
    private TranslatorInterface $translator;

    /**
     * @var ContentNodeDeletionCheckerInterface[]
     */
    private iterable $checkers;

    public function __construct(TranslatorInterface $translator, iterable $checkers)
    {
        $this->translator = $translator;
        $this->checkers = $checkers;
    }

    public function checkOnNotDeletableContentNodeUsingTree(ContentNode $contentNode): ?NotDeletableContentNodeResult
    {
        $result = $this->doCheck($contentNode);

        if (!$result) {
            return null;
        }

        if ($contentNode !== $result->getReferencedContendNode()) {
            $result->setIsChild();
        }

        return $result;
    }

    public function getDeletionWarningMessage(NotDeletableContentNodeResult $context): ?string
    {
        if (!$messageParams = $context->getWarningMessageParams()) {
            return null;
        }

        if (!$context->isChild()) {
            return $this->translator->trans(
                'oro.webcatalog.contentnode.denied_deletion_itself',
                $messageParams,
                'validators'
            );
        }

        return $this->translator->trans('oro.webcatalog.contentnode.denied_deletion_due_to_child', [
            '%nodeName%' => $context->getReferencedContendNode()->getTitle(),
            ...$messageParams
        ], 'validators');
    }

    private function doCheck(ContentNode $contentNode): ?NotDeletableContentNodeResult
    {
        foreach ($this->checkers as $checker) {
            $result = $checker->check($contentNode);

            if ($result) {
                $result->setReferencedContendNode($contentNode);

                return $result;
            }
        }

        foreach ($contentNode->getChildNodes() as $childNode) {
            if ($result = $this->doCheck($childNode)) {
                return $result;
            }
        }

        return null;
    }
}
