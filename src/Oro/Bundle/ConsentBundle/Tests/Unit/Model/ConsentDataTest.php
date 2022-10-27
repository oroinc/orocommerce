<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Model;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentDataTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testConstructor()
    {
        $consent = $this->getEntity(Consent::class, ['id' => 13, 'mandatory' => true]);

        $consentData = new ConsentData($consent);

        $this->assertEquals(13, $consentData->getId());
        $this->assertEquals(true, $consentData->isRequired());
    }

    public function testProperties()
    {
        $consent = $this->getEntity(Consent::class, ['id' => 13, 'mandatory' => true]);
        $consentData = new ConsentData($consent);
        $cmsPageData = new CmsPageData();

        $properties = [
            ['id', 13, false],
            ['title', 'consentDataTitle'],
            ['cmsPageData', $cmsPageData],
            ['accepted', true],
            ['required', true, false],
        ];
        $this->assertPropertyAccessors($consentData, $properties);
    }

    public function testJsonSerialize()
    {
        $consent = $this->getEntity(Consent::class, [
            'id' => 13,
            'mandatory' => true,
        ]);
        $consentData = new ConsentData($consent);
        $consentData->setTitle('consentTitle');
        $consentData->setAccepted(true);

        $this->assertEquals(
            [
                'consentId' => 13,
                'required' => true,
                'consentTitle' => 'consentTitle',
                'accepted' => true,
            ],
            $consentData->jsonSerialize()
        );
    }
}
