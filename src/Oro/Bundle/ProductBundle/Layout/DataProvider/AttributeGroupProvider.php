<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\EntityExtendBundle\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AttributeGroup;

class AttributeGroupProvider
{
    /** @var array */
    private $groupsRegistry = [];

    /**
     * @param AttributeFamily $attributeFamily
     * @param $groupCode
     * @return AttributeGroup
     * @throws \InvalidArgumentException
     */
    public function getGroup(AttributeFamily $attributeFamily, $groupCode)
    {
        $groups = $this->getGroupsRegistry($attributeFamily);

        if (!isset($groups[$groupCode])) {
            throw new \InvalidArgumentException('Not existing group code passed - ' . $groupCode);
        }

        if ($groups[$groupCode]['used']) {
            throw new \InvalidArgumentException('Passed group code already used - ' . $groupCode);
        }

        $this->groupsRegistry[$attributeFamily->getCode()][$groupCode]['used'] = true;

        return $groups[$groupCode]['group'];
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return AttributeGroup[]
     */
    public function getUnusedGroups(AttributeFamily $attributeFamily)
    {
        $groups = $this->getGroupsRegistry($attributeFamily);
        $unused = [];
        foreach ($groups as $group) {
            if (!$group['used']) {
                $unused[] = $group['group'];
            }
        }

        return $unused;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return array
     */
    private function getGroupsRegistry(AttributeFamily $attributeFamily)
    {
        $familyCode = $attributeFamily->getCode();
        if (isset($this->groupsRegistry[$familyCode])) {
            return $this->groupsRegistry[$familyCode];
        }
        $this->groupsRegistry[$familyCode] = [];

        /** @var AttributeGroup $group */
        foreach ($attributeFamily->getAttributeGroups() as $group) {
            $this->groupsRegistry[$familyCode][$group->getCode()] = [
                'group' => $group,
                'used' => false
            ];
        }

        return $this->groupsRegistry[$familyCode];
    }
}
