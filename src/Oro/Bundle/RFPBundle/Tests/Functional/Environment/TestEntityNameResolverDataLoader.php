<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Request::class === $entityClass) {
            $request = new Request();
            $request->setOrganization($repository->getReference('organization'));
            $request->setOwner($repository->getReference('user'));
            $request->setEmail('rfp_request@example.com');
            $request->setPhone('123-123');
            $request->setCompany('Test Company');
            $request->setFirstName('Amanda');
            $request->setLastName('Doo');
            $repository->setReference('rfpRequest', $request);
            $em->persist($request);
            $em->flush();

            return ['rfpRequest'];
        }

        if (RequestAdditionalNote::class === $entityClass) {
            $request = new Request();
            $request->setOrganization($repository->getReference('organization'));
            $request->setOwner($repository->getReference('user'));
            $request->setEmail('rfp_request@example.com');
            $request->setPhone('123-123');
            $request->setCompany('Test Company');
            $request->setFirstName('Amanda');
            $request->setLastName('Doo');
            $em->persist($request);
            $requestAdditionalNote = new RequestAdditionalNote();
            $requestAdditionalNote->setRequest($request);
            $requestAdditionalNote->setType(RequestAdditionalNote::TYPE_CUSTOMER_NOTE);
            $requestAdditionalNote->setAuthor('John Doo');
            $requestAdditionalNote->setText('Test Note');
            $requestAdditionalNote->setUserId($repository->getReference('user')->getId());
            $repository->setReference('rfpRequestAdditionalNote', $requestAdditionalNote);
            $em->persist($requestAdditionalNote);
            $em->flush();

            return ['rfpRequestAdditionalNote'];
        }

        if (RequestProduct::class === $entityClass) {
            $product = new Product();
            $product->setOrganization($repository->getReference('organization'));
            $product->setSku('RFP_TEST_PRODUCT');
            $product->addName($this->createProductName($em, 'Test RFP Product'));
            $em->persist($product);
            $requestProductItem = new RequestProductItem();
            $requestProductItem->setPrice(Price::create(100, 'USD'));
            $requestProductItem->setQuantity(1);
            $requestProductItem->setProductUnit($em->find(ProductUnit::class, 'item'));
            $em->persist($requestProductItem);
            $requestProduct = new RequestProduct();
            $requestProduct->setProduct($product);
            $requestProduct->setComment('Test Comment');
            $requestProduct->addRequestProductItem($requestProductItem);
            $repository->setReference('rfpRequestProduct', $requestProduct);
            $em->persist($requestProduct);
            $em->flush();

            return ['rfpRequestProduct'];
        }

        if (RequestProductItem::class === $entityClass) {
            $requestProductItem = new RequestProductItem();
            $requestProductItem->setPrice(Price::create(100, 'USD'));
            $requestProductItem->setQuantity(1);
            $requestProductItem->setProductUnit($em->find(ProductUnit::class, 'item'));
            $repository->setReference('rfpRequestProductItem', $requestProductItem);
            $em->persist($requestProductItem);
            $em->flush();

            return ['rfpRequestProductItem'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Request::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? 'Amanda'
                : 'Amanda Doo';
        }
        if (RequestAdditionalNote::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? (string)$repository->getReference($entityReference)->getId()
                : 'customer_note John Doo';
        }
        if (RequestProduct::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? (string)$repository->getReference($entityReference)->getId()
                : 'RFP_TEST_PRODUCT';
        }
        if (RequestProductItem::class === $entityClass) {
            return 'item';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createProductName(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): ProductName {
        $name = new ProductName();
        $name->setString($value);
        if (null !== $localization) {
            $name->setLocalization($localization);
        }
        $em->persist($name);

        return $name;
    }
}
