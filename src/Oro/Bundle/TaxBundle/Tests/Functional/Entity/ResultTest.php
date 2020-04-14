<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ResultTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->em = $this->client->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityManager(TaxValue::class);
    }

    public function testThatTaxValueCanBeSavedWithResult()
    {
        $taxValue = new TaxValue();
        $taxValue->setEntityClass('test entity class');
        $taxValue->setEntityId(777);
        $taxValue->setAddress('test address');

        $totalIncludingTax = 111;
        $totalExcludingTax = 222;

        $shippingIncludingTax = 333;
        $shippingExcludingTax = 444;

        $unitIncludingTax = 555;
        $unitExcludingTax = 666;

        $rowIncludingTax = 777;
        $rowExcludingTax = 888;

        $totalTaxes = 999;

        $result = new Result();
        $result->lockResult();
        $result->offsetSet(Result::TOTAL, ResultElement::create($totalIncludingTax, $totalExcludingTax));
        $result->offsetSet(Result::SHIPPING, ResultElement::create($shippingIncludingTax, $shippingExcludingTax));
        $result->offsetSet(Result::UNIT, ResultElement::create($unitIncludingTax, $unitExcludingTax));
        $result->offsetSet(Result::ROW, ResultElement::create($rowIncludingTax, $rowExcludingTax));
        $result->offsetSet(Result::TAXES, $totalTaxes);
        $result->offsetSet(Result::ITEMS, []);
        $taxValue->setResult($result);

        $this->em->persist($taxValue);
        $this->em->flush();
        $id = $taxValue->getId();
        $this->assertTrue($result->isResultLocked());
        $this->em->clear();

        $taxValue = $this->em->getRepository(TaxValue::class)->find($id);
        $this->assertEquals('test entity class', $taxValue->getEntityClass());
        $this->assertEquals(777, $taxValue->getEntityId());
        $this->assertEquals('test address', $taxValue->getAddress());
        $this->assertInstanceOf(\DateTime::class, $taxValue->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $taxValue->getUpdatedAt());

        $result = $taxValue->getResult();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isResultLocked());
        $this->assertEquals($totalTaxes, $result->offsetGet(Result::TAXES));
        $this->assertFalse($result->offsetExists(Result::ITEMS));
        $this->assertInstanceOf(ResultElement::class, $result->offsetGet(Result::TOTAL));
        $this->assertInstanceOf(ResultElement::class, $result->offsetGet(Result::SHIPPING));
        $this->assertInstanceOf(ResultElement::class, $result->offsetGet(Result::UNIT));
        $this->assertInstanceOf(ResultElement::class, $result->offsetGet(Result::ROW));
        $this->assertEquals($totalIncludingTax, $result->getTotal()->getIncludingTax());
        $this->assertEquals($totalExcludingTax, $result->getTotal()->getExcludingTax());
        $this->assertEquals($shippingIncludingTax, $result->getShipping()->getIncludingTax());
        $this->assertEquals($shippingExcludingTax, $result->getShipping()->getExcludingTax());
        $this->assertEquals($unitIncludingTax, $result->getUnit()->getIncludingTax());
        $this->assertEquals($unitExcludingTax, $result->getUnit()->getExcludingTax());
        $this->assertEquals($rowIncludingTax, $result->getRow()->getIncludingTax());
        $this->assertEquals($rowExcludingTax, $result->getRow()->getExcludingTax());
    }
}
