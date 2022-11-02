<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Action;

use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentTermActionTest extends WebTestCase
{
    use OperationAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPaymentTermData::class]);
    }

    public function testDelete()
    {
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference(
            LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_10
        );
        $termId = $paymentTerm->getId();
        $operationName = 'oro_payment_term_delete';
        $entityClass = PaymentTerm::class;

        $params = $this->getOperationExecuteParams($operationName, $termId, $entityClass);
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityId' => $termId,
                    'entityClass' => $entityClass,
                ]
            ),
            $params,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        self::getContainer()->get('doctrine')->getManagerForClass(PaymentTerm::class)->clear();

        $removedTerm = self::getContainer()->get('doctrine')
            ->getRepository(PaymentTerm::class)
            ->find($termId);

        self::assertNull($removedTerm);
    }
}
