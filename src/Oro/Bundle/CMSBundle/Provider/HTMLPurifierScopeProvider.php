<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Symfony\Component\Security\Core\Role\Role;

/**
 * Provide purifier scope corresponds to content_restrictions mode
 */
class HTMLPurifierScopeProvider
{
    private const UNSECURE_MODE = 'unsecure';
    private const DEFAULT_SCOPE = 'default';

    /**
     * @var TokenAccessor
     */
    private $tokenAccessor;

    /**
     * @var array
     */
    private $contentRestrictions = [];

    /**
     * @var string
     */
    private $contentRestrictionsMode;

    /**
     * @var array
     */
    private $scopeMap = [];

    public function __construct(
        TokenAccessor $tokenAccessor,
        string $contentRestrictionsMode,
        array $contentRestrictions
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->contentRestrictionsMode = $contentRestrictionsMode;
        $this->contentRestrictions = $contentRestrictions;
    }

    public function addScopeMapping(string $mode, ?string $scope)
    {
        $this->scopeMap[$mode] = $scope;
    }

    public function getScope(string $entityName, string $fieldName): ?string
    {
        if ($this->contentRestrictionsMode === self::UNSECURE_MODE) {
            return null;
        }

        $availableRoles = $this->getUserRoles();
        $useSecureMode = true;
        foreach ($availableRoles as $role) {
            if (!$this->isRoleSupport($role)) {
                continue;
            }

            $roleKey = $role->getRole();
            foreach ($this->contentRestrictions[$roleKey] as $restrictionEntityName => $restrictionFields) {
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
     * @return array|Role[]
     */
    private function getUserRoles(): array
    {
        $token = $this->tokenAccessor->getToken();

        return $token ? $token->getRoles() : [];
    }

    private function isRoleSupport(Role $role): bool
    {
        return array_key_exists($role->getRole(), $this->contentRestrictions);
    }
}
