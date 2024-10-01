<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

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
        if (PriceList::class === $entityClass) {
            $priceList = new PriceList();
            $priceList->setOrganization($repository->getReference('organization'));
            $priceList->setActive(true);
            $priceList->setName('Test Price List');
            $repository->setReference('priceList', $priceList);
            $em->persist($priceList);
            $em->flush();

            return ['priceList'];
        }

        if (PriceAttributePriceList::class === $entityClass) {
            $priceList = new PriceAttributePriceList();
            $priceList->setOrganization($repository->getReference('organization'));
            $priceList->setName('Test Attribute Price List');
            $priceList->setFieldName('test');
            $repository->setReference('priceList', $priceList);
            $em->persist($priceList);
            $em->flush();

            return ['priceList'];
        }

        if (ProductPrice::class === $entityClass) {
            $priceList = new PriceList();
            $priceList->setOrganization($repository->getReference('organization'));
            $priceList->setActive(true);
            $priceList->setName('Test Price List for Product Price');
            $em->persist($priceList);
            $priceRule = new PriceRule();
            $priceRule->setPriceList($priceList);
            $priceRule->setRule('Test Price Rule');
            $priceRule->setCurrency('USD');
            $priceRule->setPriority(1);
            $em->persist($priceRule);
            $product = new Product();
            $product->setOrganization($repository->getReference('organization'));
            $product->setSku('PP_TEST_PRODUCT');
            $product->addName($this->createProductName($em, 'Test Shopping List Kit Product'));
            $em->persist($product);

            $productPrice = new ProductPrice();
            $productPrice->setPriceList($priceList);
            $productPrice->setPriceRule($priceRule);
            $productPrice->setProduct($product);
            $productPrice->setPrice(Price::create(10.5, 'USD'));
            $productPrice->setQuantity(1.2);
            $productPrice->setUnit($em->find(ProductUnit::class, 'item'));
            $productPrice->setVersion(1);
            $repository->setReference('productPrice', $productPrice);
            $em->persist($productPrice);

            $productPriceWithIntValues = new ProductPrice();
            $productPriceWithIntValues->setPriceList($priceList);
            $productPriceWithIntValues->setPriceRule($priceRule);
            $productPriceWithIntValues->setProduct($product);
            $productPriceWithIntValues->setPrice(Price::create(10, 'USD'));
            $productPriceWithIntValues->setQuantity(1);
            $productPriceWithIntValues->setUnit($em->find(ProductUnit::class, 'item'));
            $productPriceWithIntValues->setVersion(1);
            $repository->setReference('productPriceWithIntValues', $productPriceWithIntValues);
            $em->persist($productPriceWithIntValues);

            $em->flush();

            return ['productPrice', 'productPriceWithIntValues'];
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
        if (PriceList::class === $entityClass) {
            return 'Test Price List';
        }
        if (PriceAttributePriceList::class === $entityClass) {
            return 'Test Attribute Price List';
        }
        if (ProductPrice::class === $entityClass) {
            if (EntityNameProviderInterface::SHORT === $format) {
                return 'PP_TEST_PRODUCT';
            }
            if ('productPriceWithIntValues' === $entityReference) {
                return 'PP_TEST_PRODUCT | 1 item | 10 USD';
            }

            return 'PP_TEST_PRODUCT | 1.2 item | 10.5 USD';
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
