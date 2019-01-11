<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Component\Testing\Unit\EntityTrait;

class RequiredConsentFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var RequiredConsentFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filter = new RequiredConsentFilter();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->filter);
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

    /**
     * @return array
     */
    public function isConsentPassedFilterProvider()
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
