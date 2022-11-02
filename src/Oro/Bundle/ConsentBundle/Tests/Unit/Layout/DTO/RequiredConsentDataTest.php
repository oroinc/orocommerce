<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Layout\DTO;

use Oro\Bundle\ConsentBundle\Layout\DTO\RequiredConsentData;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent;

class RequiredConsentDataTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct(): void
    {
        $requiredConsentData = new RequiredConsentData();

        self::assertSame(0, $requiredConsentData->getRequiredConsentsNumber());
        self::assertSame([], $requiredConsentData->getAcceptedRequiredConsentData());
    }

    public function testGetRequiredConsentsNumber(): void
    {
        self::assertSame(100, (new RequiredConsentData([], 100))->getRequiredConsentsNumber());
    }

    public function testGetAcceptedRequiredConsentData(): void
    {
        $consentData = [new ConsentData(new Consent()), new ConsentData(new Consent())];

        self::assertSame($consentData, (new RequiredConsentData($consentData, 100))->getAcceptedRequiredConsentData());
    }
}
