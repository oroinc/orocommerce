<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Controller;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadCouponData::class,
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_promotion_coupon_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('promotion-coupons-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_promotion_coupon_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Create Coupon', $crawler->html());
    }

    public function testUpdate()
    {
        /** @var Coupon $coupon */
        $coupon = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class)
            ->findOneBy([]);
        $this->client->request('GET', $this->getUrl('oro_promotion_coupon_update', ['id' => $coupon->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        /** @var Coupon $coupon */
        $coupon = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class)
            ->findOneBy([]);
        $this->client->request('GET', $this->getUrl('oro_promotion_coupon_view', ['id' => $coupon->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCouponGenerationPreview()
    {
        $request = [
            'couponGenerationData' => [
                'codeLength' => 3,
            ],
        ];
        $this->ajaxRequest('POST', $this->getUrl('oro_promotion_coupon_generation_preview'), $request);
        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }
}
