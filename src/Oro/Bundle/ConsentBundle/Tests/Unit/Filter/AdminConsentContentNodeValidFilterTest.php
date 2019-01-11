<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\AdminConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Validator\ConsentContentNodeValidator;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class AdminConsentContentNodeValidFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var ConsentContentNodeValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentNodeValidator;

    /**
     * @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentAcceptanceProvider;

    /**
     * @var AdminConsentContentNodeValidFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->contentNodeValidator = $this->createMock(ConsentContentNodeValidator::class);
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);
        $this->filter = new AdminConsentContentNodeValidFilter(
            $this->frontendHelper,
            $this->contentNodeValidator,
            $this->consentAcceptanceProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->frontendHelper,
            $this->contentNodeValidator,
            $this->filter
        );
    }

    public function testIsConsentPassedFilterNoContentNode()
    {
        $consent = $this->getEntity(Consent::class, ['id' => 999]);

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->consentAcceptanceProvider->expects($this->never())
            ->method('getCustomerConsentAcceptanceByConsentId');

        $this->contentNodeValidator->expects($this->never())
            ->method('isValid');

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterFrontendRequest()
    {
        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(ContentNode::class)
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->consentAcceptanceProvider->expects($this->never())
            ->method('getCustomerConsentAcceptanceByConsentId');

        $this->contentNodeValidator->expects($this->never())
            ->method('isValid');

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterConsentAcceptanceExists()
    {
        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(ContentNode::class)
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->consentAcceptanceProvider->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn($consentAcceptance);

        $this->contentNodeValidator->expects($this->never())
            ->method('isValid');

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterValidContentNode()
    {
        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(ContentNode::class)
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->consentAcceptanceProvider->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn(null);

        $this->contentNodeValidator->expects($this->once())
            ->method('isValid')
            ->with($consent->getContentNode(), $consent)
            ->willReturn(true);

        $this->assertTrue($this->filter->isConsentPassedFilter($consent));
    }

    public function testIsConsentPassedFilterInvalidContentNode()
    {
        $consent = $this->getEntity(
            Consent::class,
            [
                'id' => 999,
                'contentNode' => $this->getEntity(ContentNode::class)
            ]
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->consentAcceptanceProvider->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn(null);

        $this->contentNodeValidator->expects($this->once())
            ->method('isValid')
            ->with($consent->getContentNode(), $consent)
            ->willReturn(false);

        $this->assertFalse($this->filter->isConsentPassedFilter($consent));
    }

    public function testGetName()
    {
        $this->assertEquals(AdminConsentContentNodeValidFilter::NAME, $this->filter->getName());
    }
}
