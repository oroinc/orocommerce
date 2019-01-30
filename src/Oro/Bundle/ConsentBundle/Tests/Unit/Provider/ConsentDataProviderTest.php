<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Builder\ConsentDataBuilder;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $enabledConsentProvider;

    /**
     * @var ConsentDataBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentDataBuilder;

    /**
     * @var ConsentDataProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->enabledConsentProvider = $this->createMock(EnabledConsentProvider::class);
        $this->consentDataBuilder = $this->createMock(ConsentDataBuilder::class);

        $this->provider = new ConsentDataProvider(
            $this->enabledConsentProvider,
            $this->consentDataBuilder
        );
    }

    public function testGetAllConsentData()
    {
        $customerUser = new CustomerUser();
        $consent = new Consent();
        $consentData = new ConsentData($consent);

        $this->enabledConsentProvider->expects($this->once())
            ->method('getConsents')
            ->with([FrontendConsentContentNodeValidFilter::NAME])
            ->willReturn([$consent]);

        $this->consentDataBuilder->expects($this->once())
            ->method('build')
            ->with($consent)
            ->willReturn($consentData);

        $this->assertEquals([$consentData], $this->provider->getAllConsentData($customerUser));
    }

    public function testGetNotAcceptedRequiredConsentData()
    {
        $customerUser = new CustomerUser();
        $consentAccepted = new Consent();
        $consentNotAccepted = new Consent();
        $consentDataAccepted = (new ConsentData($consentAccepted))->setAccepted(true);
        $consentDataNotAccepted = (new ConsentData($consentNotAccepted))->setAccepted(false);

        $this->enabledConsentProvider->expects($this->once())
            ->method('getConsents')
            ->with([
                FrontendConsentContentNodeValidFilter::NAME,
                RequiredConsentFilter::NAME
            ])
            ->willReturn([$consentAccepted, $consentNotAccepted]);

        $this->consentDataBuilder->expects($this->exactly(2))
            ->method('build')
            ->withConsecutive([$consentAccepted], [$consentNotAccepted])
            ->willReturnOnConsecutiveCalls($consentDataAccepted, $consentDataNotAccepted);

        $this->assertEquals(
            [$consentDataNotAccepted],
            $this->provider->getNotAcceptedRequiredConsentData($customerUser)
        );
    }
}
