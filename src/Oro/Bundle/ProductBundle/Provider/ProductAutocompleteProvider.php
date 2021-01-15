<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the result data for search autocomplete request.
 */
class ProductAutocompleteProvider
{
    protected UrlGeneratorInterface $urlGenerator;
    protected ProductRepository $searchRepository;
    protected HtmlTagExtension $htmlTagExtension;
    protected ImagePlaceholderProviderInterface $imagePlaceholderProvider;
    protected ConfigManager $configManager;
    protected EnumValueProvider $enumValueProvider;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ProductRepository $searchRepository,
        HtmlTagExtension $htmlTagExtension,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        ConfigManager $configManager,
        EnumValueProvider $enumValueProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->searchRepository = $searchRepository;
        $this->htmlTagExtension = $htmlTagExtension;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->configManager = $configManager;
        $this->enumValueProvider = $enumValueProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $queryString
     * @return array ['total_count' => int, 'products' => [<productSku> => ...]]
     */
    public function getAutocompleteData(Request $request, $queryString): array
    {
        $numberOfProducts = $this->configManager
            ->get(Configuration::getConfigKeyByName(Configuration::SEARCH_AUTOCOMPLETE_MAX_PRODUCTS));

        $result = $this->searchRepository
            ->getAutocompleteSearchQuery($queryString, $numberOfProducts)
            ->getResult();

        return [
            'total_count' => $result->getRecordsCount(),
            'products' => $this->getProductData($result->getElements(), $request),
        ];
    }

    /**
     * @param array|Item[]  $productItems
     * @param Request $request
     *
     * @return array
     */
    protected function getProductData(array $productItems, Request $request): array
    {
        $defaultImage = $this->imagePlaceholderProvider->getPath('product_small');
        $inventoryStatuses = array_flip($this->enumValueProvider->getEnumChoicesByCode('prod_inventory_status'));

        $data = [];
        foreach ($productItems as $item) {
            $productId = $item->getRecordId();

            $productData = $item->getSelectedData();
            $productData['id'] = $productId;
            $productData['url'] = $this->urlGenerator->generate(
                'oro_product_frontend_product_view',
                ['id' => $productId]
            );

            if (!empty($productData['image'])) {
                $productData['image'] = $request->getUriForPath($productData['image']);
            } else {
                $productData['image'] = $defaultImage;
            }

            // set default image for cases when original image is missing
            $productData['default_image'] = $defaultImage;

            $inventoryStatus = $productData['inventory_status'];
            $productData['inventory_status_label'] = $inventoryStatuses[$inventoryStatus] ?? $inventoryStatus;

            $sku = $productData['sku'];
            $data[$sku] = $productData;
        }

        $event = new ProcessAutocompleteDataEvent($data);
        $this->eventDispatcher->dispatch($event);

        return $this->sanitize($event->getData(), 'products');
    }

    /**
     * Walk array to sanitize array values
     * @param array $data
     * @param null $key
     *
     * @return array
     */
    protected function sanitize(array $data, $key = null): array
    {
        $userDefinedFields = ['sku', 'name', 'image', 'inventory_status', 'inventory_status_label'];

        if ($key && isset($data[$key])) {
            $data = $data[$key];
        }

        array_walk_recursive($data, function (&$value, $key) use ($userDefinedFields) {
            if (in_array($key, $userDefinedFields, false)) {
                $value = $this->htmlTagExtension->htmlSanitize($value);
            }
        });

        return $data;
    }
}
