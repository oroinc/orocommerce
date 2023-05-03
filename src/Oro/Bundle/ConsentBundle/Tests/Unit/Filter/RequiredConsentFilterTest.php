<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Component\Testing\Unit\EntityTrait;

class RequiredConsentFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RequiredConsentFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new RequiredConsentFilter();
    }

    /**
     * @dataProvider isConsentPassedFilterProvider
     *
     * @param Consent $consent
     * @param bool $expectedResult
     */
    public function testIsConsentPassedFilter(Consent $consent, $expectedResult)
    {
        $result = $this->filter->isConsentPassedFilter($consent);
        $this->assertEquals($expectedResult, $result);
    }

    public function isConsentPassedFilterProvider(): array
    {
        return [
            'Mandatory consent' => [
                'consent' => $this->getEntity(Consent::class, ['id' => 1, 'mandatory' => true]),
                'expectedResult' => true
            ],
            'Optional consent' => [
                'consent' => $this->getEntity(Consent::class, ['id' => 1, 'mandatory' => false]),
                'expectedResult' => false
            ]
        ];
    }
}
