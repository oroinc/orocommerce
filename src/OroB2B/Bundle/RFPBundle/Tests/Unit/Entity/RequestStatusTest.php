<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatusTranslation;

class RequestStatusTest extends EntityTestCase
{
    /**
     * Test setters getters
     */
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

        static::assertPropertyAccessors($propertyRequestStatus, $properties);
    }

    /**
     * Test translation setters getters
     */
    public function testTranslation()
    {
        $requestStatus = new RequestStatus();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $requestStatus->getTranslations());
        $this->assertCount(0, $requestStatus->getTranslations());

        $translation = new RequestStatusTranslation();

        $requestStatus->addTranslation($translation);

        $this->assertCount(1, $requestStatus->getTranslations());

        $requestStatus->addTranslation($translation);

        $this->assertCount(1, $requestStatus->getTranslations());

        $requestStatus->addTranslation(new RequestStatusTranslation());

        $this->assertCount(2, $requestStatus->getTranslations());

        $translation = new RequestStatusTranslation();
        $translation
            ->setLocale('en_US')
            ->setField('type');

        $translations = new ArrayCollection([$translation]);
        $requestStatus->setTranslations($translations);
        $this->assertCount(1, $requestStatus->getTranslations());
    }

    /**
     * Test toString
     */
    public function testToString()
    {
        $value = 'Opened';

        $requestStatus = new RequestStatus();
        $requestStatus->setLabel($value);

        $this->assertEquals($value, (string)$requestStatus);
    }
}
