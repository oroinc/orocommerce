<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class LoadOrderDocuments extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var FileLocator $fileLocator */
        $fileLocator = $this->container->get('file_locator');
        $filePath = $fileLocator->locate('@OroAttachmentBundle/Tests/Functional/DataFixtures/files/file_1.txt');

        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $file = new File();
        $file->setFile(new ComponentFile($filePath));
        $file->setOriginalFilename('file_1.txt');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('file1', $file);

        $file = new File();
        $file->setFile(new ComponentFile($filePath));
        $file->setOriginalFilename('file_2.txt');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('file2', $file);

        $file = new File();
        $file->setFile(new ComponentFile($filePath));
        $file->setOriginalFilename('file_3.txt');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('file3', $file);

        /** @var Order $order */
        $order = $this->getReference('order1');
        $order->setDocuments(new ArrayCollection([
            $this->createFileItem($this->getReference('file3'), 3),
            $this->createFileItem($this->getReference('file2'), 1),
            $this->createFileItem($this->getReference('file1'), 2),
        ]));

        $manager->flush();
    }

    private function createFileItem(File $file, int $sortOrder): FileItem
    {
        $fileItem = new FileItem();
        $fileItem->setFile($file);
        $fileItem->setSortOrder($sortOrder);

        return $fileItem;
    }
}
