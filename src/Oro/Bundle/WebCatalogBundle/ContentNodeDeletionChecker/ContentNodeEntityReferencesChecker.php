<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeDeletionChecker;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\WebCatalogBundle\Context\NotDeletableContentNodeResult;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks on references in ContentNode entity relations for Content Node
 */
class ContentNodeEntityReferencesChecker implements ContentNodeDeletionCheckerInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function check(ContentNode $contentNode): ?NotDeletableContentNodeResult
    {
        $result = new NotDeletableContentNodeResult();

        if ($menuReferences = $contentNode->getReferencedMenuItems()) {
            foreach ($menuReferences as $menuReference) {
                if ($menuReference->isCustom() || $this->isRootMenuUpdate($menuReference)) {
                    $result->setWarningMessageParams([
                        '%key%' => $menuReference->getMenu(),
                        '%subject%' => $this->translator->trans('oro.commercemenu.menuupdate.menu.label')
                    ]);

                    return $result;
                }
            }
        }

        if (!$contentNode->getReferencedConsents()->isEmpty()) {
            $usedConsent = $contentNode->getReferencedConsents()->first();

            $result->setWarningMessageParams([
                '%key%' => $usedConsent->getName(),
                '%subject%' => $this->translator->trans('oro.consent.menu.management.label')
            ]);

            return $result;
        }

        return null;
    }

    private function isRootMenuUpdate(MenuUpdateInterface $menuReference): bool
    {
        return $menuReference->getKey() === $menuReference->getMenu() && is_null($menuReference->getParentKey());
    }
}
