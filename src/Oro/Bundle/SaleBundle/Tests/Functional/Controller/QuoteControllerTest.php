<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

class QuoteControllerTest extends WebTestCase
{
    use OperationAwareTestTrait;

    private const VALID_UNTIL = '2015-05-15T15:15:15+0000';
    private const VALID_UNTIL_UPDATED = '2016-06-16T16:16:16+0000';
    private const PO_NUMBER = 'CA3333USD';
    private const PO_NUMBER_UPDATED = 'CA5555USD';
    private const SHIP_UNTIL = '2015-09-15T00:00:00+0000';
    private const SHIP_UNTIL_UPDATED = '2015-09-20T00:00:00+0000';
    private const OVERRIDDEN_SHIPPING_COST_AMOUNT = '999.9900';
    private const OVERRIDDEN_SHIPPING_COST_CURRENCY = 'USD';

    private static string $qid;
    private static string $qidUpdated;

    public static function setUpBeforeClass(): void
    {
        self::$qid = 'TestQuoteID - ' . time() . '-' . mt_rand();
        self::$qidUpdated = self::$qid . ' - updated';
    }

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadUserData::class, LoadPaymentTermData::class]);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_sale_quote_create'));
        $owner = $this->getReferencedUser(LoadUserData::USER1);

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('oro_sale_quote[quoteProducts][0]');
        $form['oro_sale_quote[owner]'] = $owner->getId();
        $form['oro_sale_quote[qid]'] = self::$qid;
        $form['oro_sale_quote[validUntil]'] = self::VALID_UNTIL;
        $form['oro_sale_quote[poNumber]'] = self::PO_NUMBER;
        $form['oro_sale_quote[shipUntil]'] = self::SHIP_UNTIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Quote has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testIndex(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_sale_quote_index'));
        $owner = $this->getReferencedUser(LoadUserData::USER1);

        $result = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('quotes-grid', $crawler->html());

        $response = $this->client->requestGrid(
            'quotes-grid',
            ['quotes-grid[_filter][qid][value]' => self::$qid]
        );

        $result = self::getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);

        $row = reset($result['data']);

        $id = $row['id'];

        $this->assertEquals(self::$qid, $row['qid']);
        $this->assertEquals($owner->getFirstName() . ' ' . $owner->getLastName(), $row['ownerName']);
        $this->assertEquals(self::VALID_UNTIL, $row['validUntil']);
        $this->assertEquals(self::PO_NUMBER, $row['poNumber']);
        $this->assertEquals(self::SHIP_UNTIL, $row['shipUntil']);

        return $id;
    }

    /**
     * @depends testIndex
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_sale_quote_update', ['id' => $id]));
        $owner = $this->getReferencedUser(LoadUserData::USER2);
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this
            ->getReference(LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_10);
        $paymentTermProperty = $this->getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->getDefaultAssociationName();

        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('oro_sale_quote[quoteProducts][0]');
        $form['oro_sale_quote[owner]'] = $owner->getId();
        $form['oro_sale_quote[qid]'] = self::$qidUpdated;
        $form['oro_sale_quote[validUntil]'] = self::VALID_UNTIL_UPDATED;
        $form['oro_sale_quote[poNumber]'] = self::PO_NUMBER_UPDATED;
        $form['oro_sale_quote[shipUntil]'] = self::SHIP_UNTIL_UPDATED;
        $form[sprintf('oro_sale_quote[%s]', $paymentTermProperty)] = $paymentTerm->getId();

        $user = $this->getReference(LoadUserData::USER1);
        $accountUser1 = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $accountUser2 = $this->getReference(LoadUserData::ACCOUNT1_USER2);

        $form['oro_sale_quote[assignedUsers]'] = $user->getId();
        $form['oro_sale_quote[assignedCustomerUsers]'] = implode(',', [$accountUser1->getId(), $accountUser2->getId()]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Quote has been saved', $crawler->html());

        /** @var Quote $quote */
        $quote = $this->getContainer()->get('doctrine')
            ->getRepository(Quote::class)
            ->find($id);

        $this->assertEquals(self::$qidUpdated, $quote->getQid());
        $this->assertEquals($owner->getId(), $quote->getOwner()->getId());
        $this->assertEquals(strtotime(self::VALID_UNTIL_UPDATED), $quote->getValidUntil()->getTimestamp());
        $this->assertEquals(self::PO_NUMBER_UPDATED, $quote->getPoNumber());
        $this->assertEquals(strtotime(self::SHIP_UNTIL_UPDATED), $quote->getShipUntil()->getTimestamp());
        $this->assertUsersExists([$user], $quote->getAssignedUsers());
        $this->assertUsersExists([$accountUser1, $accountUser2], $quote->getAssignedCustomerUsers());

        $accessor = $this->getContainer()->get('oro_payment_term.provider.payment_term_association');
        $this->assertEquals($paymentTerm->getId(), $accessor->getPaymentTerm($quote)->getId());

        return $id;
    }

    private function assertUsersExists(array $expectedUsers, Collection $actualUsers): void
    {
        $callable = function (AbstractUser $user) {
            return $user->getId();
        };

        $expectedUserIds = array_map($callable, $expectedUsers);
        $actualUserIds = array_map($callable, $actualUsers->toArray());

        foreach ($expectedUserIds as $expectedUserId) {
            $this->assertContains($expectedUserId, $actualUserIds);
        }
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateOverriddenShippingCost(int $id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_sale_quote_update', ['id' => $id]));

        $form = $crawler->selectButton('Save')->form();
        $form['oro_sale_quote[overriddenShippingCostAmount][value]'] = self::OVERRIDDEN_SHIPPING_COST_AMOUNT;
        $form['oro_sale_quote[overriddenShippingCostAmount][currency]'] = self::OVERRIDDEN_SHIPPING_COST_CURRENCY;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $form = $crawler->selectButton('Save')->form();
        $fields = $form->get('oro_sale_quote');
        $this->assertEquals(
            self::OVERRIDDEN_SHIPPING_COST_AMOUNT,
            $fields['overriddenShippingCostAmount']['value']->getValue()
        );
        $this->assertEquals(
            self::OVERRIDDEN_SHIPPING_COST_CURRENCY,
            $fields['overriddenShippingCostAmount']['currency']->getValue()
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testUpdate
     */
    public function testView(int $id): int
    {
        $this->client->request('GET', $this->getUrl('oro_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();

        self::assertStringContainsString(
            $this->getReference(LoadUserData::USER1)->getFullName(),
            $result->getContent()
        );
        self::assertStringContainsString(
            $this->getReference(LoadUserData::ACCOUNT1_USER1)->getFullName(),
            $result->getContent()
        );
        self::assertStringContainsString(
            $this->getReference(LoadUserData::ACCOUNT1_USER2)->getFullName(),
            $result->getContent()
        );

        self::assertHtmlResponseStatusCodeEquals($result, 200);

        return $id;
    }

    /**
     * @depends testView
     */
    public function testDelete(int $id)
    {
        $operationName = 'DELETE';
        $entityClass   = Quote::class;
        $operationExecuteParams = $this->getOperationExecuteParams($operationName, $id, $entityClass);
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityId' => $id,
                    'entityClass' => $entityClass,
                ]
            ),
            $operationExecuteParams,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('oro_sale_quote_index'),
                'pageReload' => true
            ],
            self::jsonToArray($this->client->getResponse()->getContent())
        );

        $this->client->request('GET', $this->getUrl('oro_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, array $expectedData)
    {
        $this->prepareProviderData($submittedData);

        $crawler = $this->client->request('GET', $this->getUrl('oro_sale_quote_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('oro_sale_quote[quoteProducts][0]');
        foreach ($submittedData as $field => $value) {
            $form[QuoteType::NAME . $field] = $value;
        }

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $filtered = $crawler->filter($expectedData['filter']);

        $this->assertEquals(1, $filtered->count());
        self::assertStringContainsString($expectedData['contains'], $filtered->html());
    }

    public function submitProvider(): array
    {
        return [
// will be fixed in BB-19539
//            'invalid owner' => [
//                'submittedData' => [
//                    '[owner]' => 333,
//                ],
//                'expectedData'  => [
//                    'contains' => 'This value is not valid',
//                    'filter' => '.validation-failed',
//                ],
//            ],
            'valid owner' => [
                'submittedData' => [
                    '[owner]' => function () {
                        return $this->getReferencedUser(LoadUserData::USER1)->getId();
                    },
                ],
                'expectedData'  => [
                    'contains'  => 'Quote has been saved',
                    'filter'    => 'body',
                ],
            ],
        ];
    }

    private function prepareProviderData(array &$data): void
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \Closure) {
                $data[$key] = $value();
            }
        }
    }

    private function getReferencedUser(string $username): User
    {
        return $this->getReference($username);
    }
}
