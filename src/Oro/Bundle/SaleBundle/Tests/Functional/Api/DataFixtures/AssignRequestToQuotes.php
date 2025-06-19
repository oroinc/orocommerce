<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

class AssignRequestToQuotes extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadQuoteData::class,
            LoadRequestData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->getQuote(LoadQuoteData::QUOTE1)->setRequest($this->getRequest(LoadRequestData::REQUEST1));
        $this->getQuote(LoadQuoteData::QUOTE2)->setRequest($this->getRequest(LoadRequestData::REQUEST1));
        $manager->flush();
    }

    private function getQuote(string $reference): Quote
    {
        return $this->getReference($reference);
    }

    private function getRequest(string $reference): Request
    {
        return $this->getReference($reference);
    }
}
