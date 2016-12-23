<?php


namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\EntityExtendBundle\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AttributeGroup;
use Oro\Bundle\ProductBundle\Layout\DataProvider\AttributeGroupProvider;

class AttributeGroupProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttributeGroupProvider */
    private $provider;

    protected function setUp()
    {
        $this->provider = new AttributeGroupProvider();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Not existing group code passed - not_existing_group
     */
    public function testGetGroupExceptionNotExist()
    {
        $this->provider->getGroup((new AttributeFamily())->setCode('code'), 'not_existing_group');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Passed group code already used - group_already_used
     */
    public function testGetGroupExceptionAlreadyUsed()
    {
        $family = (new AttributeFamily())->setCode('code');
        $family->addAttributeGroup((new AttributeGroup())->setCode('group_already_used'));
        $this->provider->getGroup($family, 'group_already_used');
        $this->provider->getGroup($family, 'group_already_used');
    }

    public function testGetGroup()
    {
        $group = (new AttributeGroup())->setCode('some_group');
        $family = (new AttributeFamily())->setCode('code')->addAttributeGroup($group);
        $this->assertSame($group, $this->provider->getGroup($family, 'some_group'));
    }

    public function testGetUnusedGroups()
    {
        $group1 = (new AttributeGroup())->setCode('some_group');
        $group2 = (new AttributeGroup())->setCode('some_group2');
        $family = (new AttributeFamily())
            ->setCode('code')
            ->addAttributeGroup($group1)
            ->addAttributeGroup($group2);

        $this->provider->getGroup($family, 'some_group');

        $this->assertSame([$group2], $this->provider->getUnusedGroups($family));
    }
}
