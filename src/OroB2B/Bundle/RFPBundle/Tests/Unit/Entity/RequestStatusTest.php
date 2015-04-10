<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatusTranslation;

class RequestStatusTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['locale', 'en'],
            ['name', 'opened'],
            ['label', 'Opened'],
            ['sortOrder', 1],
            ['deleted', true],
            ['deleted', false],
        ];

        $propertyRequestStatus = new RequestStatus();

        $this->assertPropertyAccessors($propertyRequestStatus, $properties);
    }

    public function testToString()
    {
        $value = 'Opened';

        $requestStatus = new RequestStatus();
        $requestStatus->setLabel($value);

        $this->assertEquals($value, (string)$requestStatus);
    }

    public function testTranslationAccessors()
    {
        $requestStatus = new RequestStatus();
        $this->assertEmpty($requestStatus->getTranslations()->toArray());

        $firstTranslation = $this->createStatusTranslation('en', 'en_label');
        $secondTranslation = $this->createStatusTranslation('ru', 'ru_label');
        $thirdTranslation = $this->createStatusTranslation('de', 'de_label');

        $requestStatus->setTranslations([$firstTranslation, $secondTranslation]);
        $this->assertEquals(
            [$firstTranslation, $secondTranslation],
            $requestStatus->getTranslations()->toArray()
        );
        $this->assertEquals($requestStatus, $firstTranslation->getObject());
        $this->assertEquals($requestStatus, $secondTranslation->getObject());

        $requestStatus->addTranslation($thirdTranslation);
        $this->assertEquals(
            [$firstTranslation, $secondTranslation, $thirdTranslation],
            $requestStatus->getTranslations()->toArray()
        );
        $this->assertEquals($requestStatus, $thirdTranslation->getObject());
    }

    /**
     * @param string $locale
     * @param string $content
     * @return RequestStatusTranslation
     */
    protected function createStatusTranslation($locale, $content)
    {
        return new RequestStatusTranslation($locale, 'label', $content);
    }
}
