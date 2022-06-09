<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;

/**
 * @group CommunityEdition
 */
class CustomerGroupControllerTest extends AbstractPriceListsByEntityTestCase
{
    private CustomerGroup $customerGroup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerGroup = $this->getReference('customer_group.group3');
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateUrl($id = null)
    {
        return $this->getUrl('oro_customer_customer_group_update', ['id' => $id ?: $this->customerGroup->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateUrl()
    {
        return $this->getUrl('oro_customer_customer_group_create');
    }

    /**
     * {@inheritdoc}
     */
    public function getViewUrl()
    {
        return $this->getUrl('oro_customer_customer_group_view', ['id' => $this->customerGroup->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMainFormName()
    {
        return CustomerGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListsByEntity()
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(PriceListToCustomerGroup::class)
            ->findBy(['customerGroup' => $this->customerGroup]);
    }
}
