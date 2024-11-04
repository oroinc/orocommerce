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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = $this->getReference('customer.level_1_1');
    }

    #[\Override]
    public function getUpdateUrl($id = null)
    {
        return $this->getUrl('oro_customer_customer_update', ['id' => $id ?: $this->customer->getId()]);
    }

    #[\Override]
    public function getCreateUrl()
    {
        return $this->getUrl('oro_customer_customer_create');
    }

    #[\Override]
    public function getViewUrl()
    {
        return $this->getUrl('oro_customer_customer_view', ['id' => $this->customer->getId()]);
    }

    #[\Override]
    public function getMainFormName()
    {
        return CustomerType::NAME;
    }

    #[\Override]
    public function getPriceListsByEntity()
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(PriceListToCustomer::class)
            ->findBy(['customer' => $this->customer]);
    }
}
