<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;

use Symfony\Component\Translation\TranslatorInterface;

class FrontendAccountUserRoleOptionsProvider
{
    /** @var array */
    private $options = [];

    /** @var RolePrivilegeCapabilityProvider */
    protected $capabilityProvider;

    /** @var RolePrivilegeCategoryProvider */
    protected $categoryProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param RolePrivilegeCapabilityProvider $capabilityProvider
     * @param RolePrivilegeCategoryProvider $categoryProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RolePrivilegeCapabilityProvider $capabilityProvider,
        RolePrivilegeCategoryProvider $categoryProvider,
        TranslatorInterface $translator
    ) {
        $this->capabilityProvider = $capabilityProvider;
        $this->categoryProvider = $categoryProvider;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getTabsOptions()
    {
        if (!array_key_exists('tabsOptions', $this->options)) {
            $tabListOptions = array_map(
                function (PrivilegeCategory $tab) {
                    return [
                        'id' => $tab->getId(),
                        'label' => $this->translator->trans($tab->getLabel())
                    ];
                },
                $this->categoryProvider->getTabbedCategories()
            );
            
            $this->options['tabsOptions'] = [
                'data' => $tabListOptions
            ];
        }
        
        return $this->options['tabsOptions'];
    }

    /**
     * @param AccountUserRole $accountUserRole
     *
     * @return mixed
     */
    public function getCapabilitySetOptions(AccountUserRole $accountUserRole)
    {
        if (!array_key_exists('capabilitySetOptions', $this->options)) {
            $this->options['capabilitySetOptions'] = [
                'data' => $this->capabilityProvider->getCapabilities($accountUserRole),
                'tabIds' => $this->categoryProvider->getTabList()
            ];
        }

        return $this->options['capabilitySetOptions'];
    }
}
