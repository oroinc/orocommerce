<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;

class LoadRequestDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $requests = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\RFPBundle\Migrations\Data\Demo\ORM\LoadRequestStatusDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $statuses = $manager->getRepository('OroB2BRFPBundle:RequestStatus')->findAll();

        $locator  = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BRFPBundle/Migrations/Data/Demo/ORM/data/requests.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 5000, ',');

        while (($data = fgetcsv($handler, 5000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $request = new Request();
            $request->setFirstName($row['first_name']);
            $request->setLastName($row['last_name']);
            $request->setEmail($row['email']);
            $request->setPhone($row['phone']);
            $request->setCompany($row['company']);
            $request->setRole($row['role']);
            $request->setBody($row['body']);

            $status = $statuses[rand(0, count($statuses) - 1)];
            $request->setStatus($status);

            $manager->persist($request);
        }

        fclose($handler);

        $manager->flush();
    }
}
