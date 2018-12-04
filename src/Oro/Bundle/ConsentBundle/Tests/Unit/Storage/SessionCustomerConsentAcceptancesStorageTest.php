<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Storage;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Storage\SessionCustomerConsentAcceptancesStorage;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionCustomerConsentAcceptancesStorageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var SessionCustomerConsentAcceptancesStorage */
    private $storage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->storage = new SessionCustomerConsentAcceptancesStorage();
        $this->storage->setDoctrineHelper($this->doctrineHelper);
        $this->storage->setStorage($this->session);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->session);
        unset($this->doctrineHelper);
        unset($this->storage);
    }

    public function testSaveData()
    {
        $consentAcceptanceWithCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'landingPage' => $this->getEntity(Page::class, ['id' => 1]),
            'consent' => $this->getEntity(Consent::class, ['id' => 1]),
        ]);

        $consentAcceptanceWithoutCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'consent' => $this->getEntity(Consent::class, ['id' => 2]),
        ]);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(
                'guest_customer_consents_accepted',
                '[{"consentId":1,"cmsPageId":1},{"consentId":2,"cmsPageId":null}]'
            );

        $this->storage->saveData([$consentAcceptanceWithCMSPage, $consentAcceptanceWithoutCMSPage]);
    }

    public function testGetData()
    {
        $consentAcceptanceWithCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'landingPage' => $this->getEntity(Page::class, ['id' => 1]),
            'consent' => $this->getEntity(Consent::class, ['id' => 1]),
        ]);

        $consentAcceptanceWithoutCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'consent' => $this->getEntity(Consent::class, ['id' => 2]),
        ]);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('createEntityInstance')
            ->willReturnCallback(function ($className) {
                return $this->getEntity($className);
            });

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($className, $id) {
                return $this->getEntity($className, ['id' => $id]);
            });

        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('guest_customer_consents_accepted')
            ->willReturn('[{"consentId":1,"cmsPageId":1},{"consentId":2,"cmsPageId":null}]');

        $this->assertEquals(
            [$consentAcceptanceWithCMSPage, $consentAcceptanceWithoutCMSPage],
            $this->storage->getData()
        );
    }

    public function testGetDataEmptySession()
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('guest_customer_consents_accepted', false)
            ->willReturn(false);

        $this->assertSame([], $this->storage->getData());
    }

    public function testGetDataInvalidValueInSession()
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('guest_customer_consents_accepted', false)
            ->willReturn('string');

        $this->assertSame([], $this->storage->getData());
    }

    public function testClearData()
    {
        $this->session->expects($this->once())
            ->method('remove')
            ->with('guest_customer_consents_accepted');

        $this->storage->clearData();
    }

    public function testHasDataTrue()
    {
        $this->session->expects($this->once())
            ->method('has')
            ->with('guest_customer_consents_accepted')
            ->willReturn(true);

        $this->assertTrue($this->storage->hasData());
    }

    public function testHasDataFalse()
    {
        $this->session->expects($this->once())
            ->method('has')
            ->with('guest_customer_consents_accepted')
            ->willReturn(false);

        $this->assertFalse($this->storage->hasData());
    }
}
