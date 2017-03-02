<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Action;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentTermActionTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadPaymentTermData::class,
        ]);
    }

    public function testDelete()
    {
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference(
            LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_10
        );
        $termId = $paymentTerm->getId();

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'oro_payment_term_delete',
                    'entityId' => $termId,
                    'entityClass' => PaymentTerm::class,
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        static::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        static::getContainer()->get('doctrine')->getManagerForClass(PaymentTerm::class)->clear();

        $removedTerm = static::getContainer()
            ->get('doctrine')
            ->getRepository('OroPaymentTermBundle:PaymentTerm')
            ->find($termId);

        static::assertNull($removedTerm);
    }
}
