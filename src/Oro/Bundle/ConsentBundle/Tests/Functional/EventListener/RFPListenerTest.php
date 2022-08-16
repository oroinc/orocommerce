<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RFPListenerTest extends WebTestCase
{
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
        $rfq = new Request();
        $rfq->setEmail('test_rfq@test.com');
        $rfq->setFirstName('Test');
        $rfq->setLastName('RFQ');

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
