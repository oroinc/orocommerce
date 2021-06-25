<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

/**
 * Provide purifier scope corresponds to content_restrictions mode
 */
class HTMLPurifierScopeProvider
{
    private const UNSECURE_MODE = 'unsecure';
    private const DEFAULT_SCOPE = 'default';

    private TokenAccessor $tokenAccessor;

    private array $contentRestrictions;

    private string $contentRestrictionsMode;

    private array $scopeMap = [];

    public function __construct(
        TokenAccessor $tokenAccessor,
        string $contentRestrictionsMode,
        array $contentRestrictions
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->contentRestrictionsMode = $contentRestrictionsMode;
        $this->contentRestrictions = $contentRestrictions;
    }

    public function addScopeMapping(string $mode, ?string $scope): void
    {
        $this->scopeMap[$mode] = $scope;
    }

    public function getScope(string $entityName, string $fieldName): ?string
    {
        if ($this->contentRestrictionsMode === self::UNSECURE_MODE) {
            return null;
        }

        $availableRoles = $this->getRolesNames();
        $useSecureMode = true;
        foreach ($availableRoles as $roleName) {
            if (!$this->isRoleSupported($roleName)) {
                continue;
            }

            foreach ($this->contentRestrictions[$roleName] as $restrictionEntityName => $restrictionFields) {
                if ($restrictionEntityName === $entityName && in_array($fieldName, $restrictionFields, true)) {
                    $useSecureMode = false;
                    break;
                }
            }
        }

        if ($useSecureMode) {
            return self::DEFAULT_SCOPE;
        }

        if (array_key_exists($this->contentRestrictionsMode, $this->scopeMap)) {
            return $this->scopeMap[$this->contentRestrictionsMode];
        }

        throw new \LogicException('Mode is not available.');
    }

    /**
     * @return string[]
     */
    private function getRolesNames(): array
    {
        $token = $this->tokenAccessor->getToken();

        return $token ? $token->getRoleNames() : [];
    }

    private function isRoleSupported(string $roleName): bool
    {
        return array_key_exists($roleName, $this->contentRestrictions);
    }
}
