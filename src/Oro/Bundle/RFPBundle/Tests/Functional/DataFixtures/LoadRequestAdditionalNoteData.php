<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;

class LoadRequestAdditionalNoteData extends AbstractFixture implements DependentFixtureInterface
{
    const NUM_CUSTOMER_NOTES = 5;
    const NUM_SELLER_NOTES = 5;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadRequestData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= LoadRequestData::NUM_REQUESTS; $i++) {
            /** @var Request $request */
            $request = $this->getReference('rfp.request.' . $i);

            for ($j = 0; $j < self::NUM_CUSTOMER_NOTES; $j++) {
                $note = new RequestAdditionalNote();
                $note->setText('Test Customer Note ' . $i)
                    ->setRequest($request)
                    ->setAuthor('Test Customer')
                    ->setUserId($request->getOwner()->getId())
                    ->setType(RequestAdditionalNote::TYPE_CUSTOMER_NOTE);

                $request->addRequestAdditionalNote($note);
            }

            for ($j = 0; $j < self::NUM_SELLER_NOTES; $j++) {
                $note = new RequestAdditionalNote();
                $note->setText('Test Seller Note ' . $i)
                    ->setRequest($request)
                    ->setAuthor('Test Seller')
                    ->setUserId($request->getOwner()->getId())
                    ->setType(RequestAdditionalNote::TYPE_SELLER_NOTE);

                $request->addRequestAdditionalNote($note);
            }
        }

        $manager->flush();
    }
}
