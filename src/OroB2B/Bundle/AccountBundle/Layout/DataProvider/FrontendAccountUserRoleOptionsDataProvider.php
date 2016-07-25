<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

class FrontendAccountUserRoleOptionsDataProvider extends AbstractServerRenderDataProvider
{
    /** @var RolePrivilegeCapabilityProvider */
    protected $capabilityProvider;

    /** @var RolePrivilegeCategoryProvider */
    protected $categoryProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $data;

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
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if ($this->data === null) {
            $role = $context->data()->get('entity');
            
            $this->data = [
                'tabsOptions' => [
                    'data' => $this->getTabListOptions()
                ],
                'capabilitySetOptions' => [
                    'data' => $this->capabilityProvider->getCapabilities($role),
                    'tabIds' => $this->categoryProvider->getTabList()
                ]
            ];
        }
        
        return $this->data;
    }

    /**
     * @return array
     */
    protected function getTabListOptions()
    {
        return array_map(
            function (PrivilegeCategory $tab) {
                return [
                    'id' => $tab->getId(),
                    'label' => $this->translator->trans($tab->getLabel())
                ];
            },
            $this->categoryProvider->getTabbedCategories()
        );
    }
}
