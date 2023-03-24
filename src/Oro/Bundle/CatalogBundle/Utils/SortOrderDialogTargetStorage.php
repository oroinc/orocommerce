<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Utils;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Storage for sort order dialog targets.
 */
class SortOrderDialogTargetStorage
{
    private const SORT_ORDER_DIALOG_TARGET = 'sortOrderDialogTargets';

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function addTarget(string $name, string|int|null $id): bool
    {
        $session = $this->getSession();
        if (!$session) {
            return false;
        }

        $targetsByName = (array) $session->get(self::SORT_ORDER_DIALOG_TARGET, []);
        $targetsByName[$name][$id] = $id;

        $session->set(self::SORT_ORDER_DIALOG_TARGET, $targetsByName);

        return true;
    }

    public function hasTarget(string $name, string|int|null $id): bool
    {
        $session = $this->getSession();
        if (!$session) {
            return false;
        }

        $targetsByName = (array) $session->get(self::SORT_ORDER_DIALOG_TARGET, []);

        return isset($targetsByName[$name][$id]);
    }

    public function removeTarget(string $name, string|int|null $id): bool
    {
        $session = $this->getSession();
        if (!$session) {
            return false;
        }

        $targetsByName = (array) $session->get(self::SORT_ORDER_DIALOG_TARGET, []);
        if (isset($targetsByName[$name][$id])) {
            unset($targetsByName[$name][$id]);
            $session->set(self::SORT_ORDER_DIALOG_TARGET, $targetsByName);

            return true;
        }

        return false;
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        if ($request->hasSession() === false) {
            return null;
        }

        return $request->getSession();
    }
}
