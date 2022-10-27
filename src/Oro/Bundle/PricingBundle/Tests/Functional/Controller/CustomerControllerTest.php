<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;

/**
 * @group CommunityEdition
 */
class CustomerControllerTest extends AbstractPriceListsByEntityTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = $this->getReference('customer.level_1_1');
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateUrl($id = null)
    {
        return $this->getUrl('oro_customer_customer_update', ['id' => $id ?: $this->customer->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateUrl()
    {
        return $this->getUrl('oro_customer_customer_create');
    }

    /**
     * {@inheritdoc}
     */
    public function getViewUrl()
    {
        return $this->getUrl('oro_customer_customer_view', ['id' => $this->customer->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMainFormName()
    {
        return CustomerType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListsByEntity()
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(PriceListToCustomer::class)
            ->findBy(['customer' => $this->customer]);
    }
}
