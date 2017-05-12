<?php

namespace Oro\Bundle\ApruveBundle\Services;

use Oro\Bundle\ApruveBundle\Provider\ApruvePublicKeyProviderInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class SignatureVerifyingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruvePublicKeyProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruvePublicKeyProvider;

    /**
     * @var SignatureVerifyingService
     */
    private $signatureVerifyingService;

    protected function setUp()
    {
        $this->apruvePublicKeyProvider = $this->createMock(ApruvePublicKeyProviderInterface::class);

        $this->signatureVerifyingService = new SignatureVerifyingService($this->apruvePublicKeyProvider);
    }

    public function testVerifyRequestSignature()
    {
        $request = new Request([], [], [], [], [], [], $this->getContent());
        $request->headers = new HeaderBag(['X-Apruve-Signature' => $this->getCorrectSignature()]);

        $this->apruvePublicKeyProvider->expects(static::once())
            ->method('getPublicKey')
            ->willReturn($this->getPublicKey());

        static::assertTrue($this->signatureVerifyingService->verifyRequestSignature($request));
    }

    public function testVerifyRequestSignatureFalse()
    {
        $request = new Request([], [], [], [], [], [], $this->getContent());
        $request->headers = new HeaderBag(['X-Apruve-Signature' => 'd3Jvbmc=']);

        $this->apruvePublicKeyProvider->expects(static::once())
            ->method('getPublicKey')
            ->willReturn($this->getPublicKey());

        static::assertFalse($this->signatureVerifyingService->verifyRequestSignature($request));
    }

    /**
     * @return string
     */
    private function getPublicKey()
    {
        return file_get_contents(__DIR__ . '/data/apruve_test_public_key.txt');
    }

    // @codingStandardsIgnoreStart
    /**
     * @return string
     */
    private function getContent()
    {
        return '{"uuid":"164f07d6dfdca4ba584e24ae1909329c","created_at":"2017-05-11T09:05:09-05:00","event":"invoice.closed","entity":{"id":"8741c6d16b0dc7c411d14fceb36a16b8","order_id":"b03c7d8658b429b68326eff6ad25346a","amount_cents":2000,"currency":"USD","status":"closed","issue_on_create":true,"created_at":"2017-05-11T09:01:55-05:00","opened_at":"2017-05-11T09:01:55-05:00","issued_at":"2017-05-11T09:01:55-05:00","due_at":"2017-06-10T00:00:00-05:00","final_state_at":"2017-05-11T09:05:09-05:00","merchant_notes":null,"merchant_invoice_id":null,"tax_cents":0,"shipping_cents":1,"links":{"self":"https://test.apruve.com/api/v4/invoices/8741c6d16b0dc7c411d14fceb36a16b8","order":"https://test.apruve.com/api/v4/orders/b03c7d8658b429b68326eff6ad25346a"},"payments":[{"id":"e1437c2b090845d7556ef715031b1129","invoice_ids":["8741c6d16b0dc7c411d14fceb36a16b8"],"payer_id":"19560a35506ff8da3e1ffbe3fae7b49f","amount_cents":2000,"refunded_amount_cents":0,"currency":"USD","failure_reason":null,"status":"captured","created_at":"2017-05-11T09:05:03-05:00","captured_at":"2017-05-11T09:05:08-05:00","state_changed_at":"2017-05-11T09:05:08-05:00","paid_out":false,"links":{"self":"https://test.apruve.com/api/v4/payments/e1437c2b090845d7556ef715031b1129","invoices":["https://test.apruve.com/api/v4/invoices/8741c6d16b0dc7c411d14fceb36a16b8"],"payer":"https://test.apruve.com/api/v4/users/824","refunds":null},"payment_method":{"type":"bank_account","card_type":null,"last4":"6789","nickname":null,"links":{"owner":"https://test.apruve.com/api/v4/users/824"}}}],"invoice_items":[{"id":"644321fa872bf846c495c39b7ca66797","invoice_id":"8741c6d16b0dc7c411d14fceb36a16b8","order_id":"b03c7d8658b429b68326eff6ad25346a","title":"Women’s Slip-On Clog","description":"These clogs are designed to deliver all day comfort and lightness. The rubber outsole is slip-resistant and flexible, while the insole is soft and formulated to be stain resistant. The non-marking soles are made to reduce foot and leg fatigue so you can feel comfortable being on your feet all day. They are easily washable and hygienic, and feature holes for ventilation around the foot bed for all-day heat releafProduct Information \u0026amp; Features:                     Catalog Page: 5235Ultra light materialAnti-fungal and anti-bacterialSlip resistantRemovable inner soleMade with EVA foamCooling ventilation holes","quantity":1,"price_ea_cents":1999,"price_total_cents":1999,"currency":"USD","product_url":"http://commerce.dev-ticket.local/app_dev.php/product/view/3","product_image_url":null,"variant_info":null,"vendor":null,"sku":"1GB82","merchant_notes":null,"links":{"self":"https://test.apruve.com/api/v4/invoices/8741c6d16b0dc7c411d14fceb36a16b8/invoice_items/13484","order":"https://test.apruve.com/api/v4/orders/b03c7d8658b429b68326eff6ad25346a","invoice":"https://test.apruve.com/api/v4/invoices/8741c6d16b0dc7c411d14fceb36a16b8"}}]}}';
    }

    /**
     * @return string
     */
    private function getCorrectSignature()
    {
        return 't6LZR4VEK8iCYCw0n7n6tnGGf1bXICJAWP/C1C48iOCukRD2FDNJuCg1tXqj38s/w75q3MkrW0bsdTK9ztpNuwwSzhUAlg+uggn59yBQUtQTmo2xavGlQCfewNI39h7q4dVNP5BLAbmuZRtDEGt4Dl7W6wjyTW325xveaC6QPu1KKfaN1lz5MeJamHZHPa1aFd8VVXx6v/X7foUjqorbj12IavfhF8dWcwttrTXEJLEaVGQp6pcPQ9EKGZsuOY/5RtpG+ViuvlxE5tO4ywcwanOS9TFJfw0fVjMUXu4ENzxXnWL7nri4LXdnn22OvNagD+YVYlBZaohoeeW1ijJPskPPMTaRgdHkcYTOwD+56RWBVsAPIecQb2k9Gel2OWYSHsNt+ojRNrWtpuEzLhvS27+Ai/1V7HNvDpURnhzB+gzX2pYBj7VWsjfg/1gQqrNtsQ63MAuToU1vscI4yVhzAv5eCFIb03sgBqzSn0hy+7eCIHxx4BgIvPa+f46zkZYapjJK4SYPwUToI07IMxhKLyLh5xJDxyaw48XnYtz3Xt9+e18ezpkjDpFY7MAqLEC1MNmmyH43FUR+bnvtwlipnmZtrXgum3XaM8wI/3QNy+aBLk2Iy9DFaCDWJWKAZFrKsjtkdOtKfTQXgKCP7saUgehbNYQH/HlszI6z9ZO75kI=';
    }
    // @codingStandardsIgnoreEnd
}
