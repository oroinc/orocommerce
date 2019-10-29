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

    /**
     * @param TokenAccessor $tokenAccessor
     * @param string $contentRestrictionsMode
     * @param array $contentRestrictions
     *
     */
    public function __construct(
        TokenAccessor $tokenAccessor,
        string $contentRestrictionsMode,
        array $contentRestrictions
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->contentRestrictionsMode = $contentRestrictionsMode;
        $this->contentRestrictions = $contentRestrictions;
    }

    /**
     * @param string $mode
     * @param string|null $scope
     */
    public function addScopeMapping(string $mode, ?string $scope)
    {
        $this->scopeMap[$mode] = $scope;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return string|null
     */
    public function getScope(string $entityName, string $fieldName): ?string
    {
        if ($this->contentRestrictionsMode === self::UNSECURE_MODE) {
            return null;
        }

        $availableRoles = $this->tokenAccessor->getUser()->getRoles();
        $useSecureMode = true;
        foreach ($availableRoles as $role) {
            $roleKey = $role->getRole();
            if (!array_key_exists($roleKey, $this->contentRestrictions)) {
                continue;
            }
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
}
