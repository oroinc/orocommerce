<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DomCrawler\Form;

class QuoteControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    public static $qid;

    /**
     * @var string
     */
    public static $qidUpdated;

    /**
     * @var string
     */
    public static $validUntil           = '2015-05-15T15:15:15+0000';

    /**
     * @var string
     */
    public static $validUntilUpdated    = '2016-06-16T16:16:16+0000';

    /**
     * @var string
     */
    public static $poNumber             = 'CA3333USD';

    /**
     * @var string
     */
    public static $poNumberUpdated      = 'CA5555USD';

    /**
     * @var string
     */
    public static $shipUntil            = '2015-09-15T00:00:00+0000';

    /**
     * @var string
     */
    public static $shipUntilUpdated     = '2015-09-20T00:00:00+0000';

    /**
     * @var string
     */
    public static $overriddenShippingCostAmount = '999.9900';

    /**
     * @var string
     */
    public static $overriddenShippingCostCurrency = 'USD';

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        self::$qid          = 'TestQuoteID - ' . time() . '-' . rand();
        self::$qidUpdated   = self::$qid . ' - updated';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadUserData::class,
            LoadPaymentTermData::class,
        ]);
    }

    public function testCreate()
    {
        $crawler    = $this->client->request('GET', $this->getUrl('oro_sale_quote_create'));
        $owner      = $this->getReferencedUser(LoadUserData::USER1);

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /* @var $form Form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('oro_sale_quote[quoteProducts][0]');
        $form['oro_sale_quote[owner]']      = $owner->getId();
        $form['oro_sale_quote[qid]']        = self::$qid;
        $form['oro_sale_quote[validUntil]'] = self::$validUntil;
        $form['oro_sale_quote[poNumber]']   = self::$poNumber;
        $form['oro_sale_quote[shipUntil]']  = self::$shipUntil;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Quote has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testIndex()
    {
        $crawler    = $this->client->request('GET', $this->getUrl('oro_sale_quote_index'));
        $owner      = $this->getReferencedUser(LoadUserData::USER1);

        $result = $this->client->getResponse();

        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('quotes-grid', $crawler->html());

        $response = $this->client->requestGrid(
            'quotes-grid',
            ['quotes-grid[_filter][qid][value]' => self::$qid]
        );

        $result = static::getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);

        $row = reset($result['data']);

        $id = $row['id'];

        $this->assertEquals(self::$qid, $row['qid']);
        $this->assertEquals($owner->getFirstName() . ' ' . $owner->getLastName(), $row['ownerName']);
        $this->assertEquals(self::$validUntil, $row['validUntil']);
        $this->assertEquals(self::$poNumber, $row['poNumber']);
        $this->assertEquals(self::$shipUntil, $row['shipUntil']);

        return $id;
    }

    /**
     * @depends testIndex
     * @param int $id
     * @return int
     */
    public function testUpdate($id)
    {
        $crawler    = $this->client->request('GET', $this->getUrl('oro_sale_quote_update', ['id' => $id]));
        $owner      = $this->getReferencedUser(LoadUserData::USER2);
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this
            ->getReference(LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_10);
        $paymentTermProperty = $this->getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->getDefaultAssociationName();

        /* @var $form Form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('oro_sale_quote[quoteProducts][0]');
        $form['oro_sale_quote[owner]'] = $owner->getId();
        $form['oro_sale_quote[qid]'] = self::$qidUpdated;
        $form['oro_sale_quote[validUntil]'] = self::$validUntilUpdated;
        $form['oro_sale_quote[poNumber]'] = self::$poNumberUpdated;
        $form['oro_sale_quote[shipUntil]'] = self::$shipUntilUpdated;
        $form[sprintf('oro_sale_quote[%s]', $paymentTermProperty)] = $paymentTerm->getId();

        $user = $this->getReference(LoadUserData::USER1);
        $accountUser1 = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $accountUser2 = $this->getReference(LoadUserData::ACCOUNT1_USER2);

        $form['oro_sale_quote[assignedUsers]'] = $user->getId();
        $form['oro_sale_quote[assignedCustomerUsers]'] = implode(',', [$accountUser1->getId(), $accountUser2->getId()]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Quote has been saved', $crawler->html());

        /** @var Quote $quote */
        $quote = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroSaleBundle:Quote')
            ->getRepository('OroSaleBundle:Quote')
            ->find($id);

        $this->assertEquals(self::$qidUpdated, $quote->getQid());
        $this->assertEquals($owner->getId(), $quote->getOwner()->getId());
        $this->assertEquals(strtotime(self::$validUntilUpdated), $quote->getValidUntil()->getTimestamp());
        $this->assertEquals(self::$poNumberUpdated, $quote->getPoNumber());
        $this->assertEquals(strtotime(self::$shipUntilUpdated), $quote->getShipUntil()->getTimestamp());
        $this->assertUsersExists([$user], $quote->getAssignedUsers());
        $this->assertUsersExists([$accountUser1, $accountUser2], $quote->getAssignedCustomerUsers());

        $accessor = $this->getContainer()->get('oro_payment_term.provider.payment_term_association');
        $this->assertEquals($paymentTerm->getId(), $accessor->getPaymentTerm($quote)->getId());

        return $id;
    }

    protected function assertUsersExists(array $expectedUsers, Collection $actualUsers)
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
     * @param int $id
     */
    public function testUpdateOverriddenShippingCost($id)
    {
        $crawler    = $this->client->request('GET', $this->getUrl('oro_sale_quote_update', ['id' => $id]));

        /* @var $form Form */
        $form = $crawler->selectButton('Save')->form();
        $form['oro_sale_quote[overriddenShippingCostAmount][value]']  = self::$overriddenShippingCostAmount;
        $form['oro_sale_quote[overriddenShippingCostAmount][currency]']  = self::$overriddenShippingCostCurrency;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $form = $crawler->selectButton('Save')->form();
        $fields = $form->get('oro_sale_quote');
        $this->assertEquals(
            self::$overriddenShippingCostAmount,
            $fields['overriddenShippingCostAmount']['value']->getValue()
        );
        $this->assertEquals(
            self::$overriddenShippingCostCurrency,
            $fields['overriddenShippingCostAmount']['currency']->getValue()
        );

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testUpdate
     * @param int $id
     * @return int
     */
    public function testView($id)
    {
        $this->client->request('GET', $this->getUrl('oro_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();

        static::assertStringContainsString(
            $this->getReference(LoadUserData::USER1)->getFullName(),
            $result->getContent()
        );
        static::assertStringContainsString(
            $this->getReference(LoadUserData::ACCOUNT1_USER1)->getFullName(),
            $result->getContent()
        );
        static::assertStringContainsString(
            $this->getReference(LoadUserData::ACCOUNT1_USER2)->getFullName(),
            $result->getContent()
        );

        static::assertHtmlResponseStatusCodeEquals($result, 200);

        return $id;
    }

    /**
     * @depends testView
     * @param int $id
     */
    public function testDelete($id)
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
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->client->request('GET', $this->getUrl('oro_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, array $expectedData)
    {
        $this->prepareProviderData($submittedData);

        $crawler = $this->client->request('GET', $this->getUrl('oro_sale_quote_create'));

        /* @var $form Form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('oro_sale_quote[quoteProducts][0]');
        foreach ($submittedData as $field => $value) {
            $form[QuoteType::NAME . $field] = $value;
        }

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $filtered = $crawler->filter($expectedData['filter']);

        $this->assertEquals(1, $filtered->count());
        static::assertStringContainsString($expectedData['contains'], $filtered->html());
    }

    /**
     * @return array
     */
    public function submitProvider()
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

    protected function prepareProviderData(array &$data)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \Closure) {
                $data[$key] = $value();
            }
        }
    }

    /**
     * @param string $username
     * @return User
     */
    protected function getReferencedUser($username)
    {
        return $this->getReference($username);
    }

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        $container->get('session')->save();

        return $tokenData;
    }
}
