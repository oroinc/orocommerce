<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class RFPListenerTest extends WebTestCase
{
    use EntityTrait;

    public function testCustomerUserGeneratedOnAnonymousCustomer()
    {
        $this->initClient();

        $this->getContainer()->get('security.token_storage')->setToken(
            new AnonymousCustomerUserToken(
                '',
                [],
                new CustomerVisitor()
            )
        );
        /** @var Request $rfq */
        $rfq = $this->getEntity(Request::class, [
            'email' => 'test_rfq@test.com',
            'first_name' => 'Test',
            'last_name' => 'RFQ'
        ]);

        $registry = $this->getContainer()->get('doctrine');
        $em = $registry->getManagerForClass(Request::class);
        $em->persist($rfq);

        $this->assertInstanceOf(
            CustomerUser::class,
            $rfq->getCustomerUser(),
            'Expected that customerUser will be generated within processing persisting of RFQ.' .
            'Listener "oro_consent.event_listener.rfq_entity_listener" depends on this behavior.'
        );
    }
}
