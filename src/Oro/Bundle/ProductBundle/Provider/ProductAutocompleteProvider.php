<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration as ProductConfiguration;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteQueryEvent;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\UIBundle\Tools\UrlHelper;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the result data for search autocomplete request.
 */
class ProductAutocompleteProvider
{
    protected const SEARCH_TYPE = 'product_autocomplete';

    protected UrlGeneratorInterface $urlGenerator;
    protected ProductRepository $searchRepository;
    protected HtmlTagExtension $htmlTagExtension;
    protected ImagePlaceholderProviderInterface $imagePlaceholderProvider;
    protected ConfigManager $configManager;
    protected EnumValueProvider $enumValueProvider;
    protected EventDispatcherInterface $eventDispatcher;
    protected UrlHelper $urlHelper;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ProductRepository $searchRepository,
        HtmlTagExtension $htmlTagExtension,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        ConfigManager $configManager,
        EnumValueProvider $enumValueProvider,
        EventDispatcherInterface $eventDispatcher,
        UrlHelper $urlHelper
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->searchRepository = $searchRepository;
        $this->htmlTagExtension = $htmlTagExtension;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->configManager = $configManager;
        $this->enumValueProvider = $enumValueProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->urlHelper = $urlHelper;
    }

    /**
     * @param string $queryString
     * @param string|null $searchSessionId
     * @return array ['total_count' => int, 'products' => [<productData>, ...], 'categories' => [<categoryData>, ...]]
     */
    public function getAutocompleteData(string $queryString, ?string $searchSessionId = null): array
    {
        $numberOfProducts = $this->configManager
            ->get(ProductConfiguration::getConfigKeyByName(ProductConfiguration::SEARCH_AUTOCOMPLETE_MAX_PRODUCTS));

        $query = $this->searchRepository->getAutocompleteSearchQuery($queryString, $numberOfProducts);

        $query->setHint(Query::HINT_SEARCH_TYPE, self::SEARCH_TYPE);
        $query->setHint(Query::HINT_SEARCH_TERM, $queryString);
        $query->setHint(Query::HINT_SEARCH_SESSION, $searchSessionId);

        $event = new ProcessAutocompleteQueryEvent($query, $queryString);
        $this->eventDispatcher->dispatch($event);

        $result = $event->getQuery()->getResult();

        $data = [
            'total_count' => $result->getRecordsCount(),
            'products' => $this->getProductData($result->getElements()),
        ];

        $event = new ProcessAutocompleteDataEvent($data, $queryString, $result);
        $this->eventDispatcher->dispatch($event);

        $data = $event->getData();

        $data['products'] = $this->sanitize($data, 'products');

        return $data;
    }

    /**
     * @param array|Item[]  $productItems
     * @param Request $request
     *
     * @return array
     */
    protected function getProductData(array $productItems): array
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
                $productData['image'] = $this->urlHelper->getAbsolutePath($productData['image']);
            } else {
                $productData['image'] = $defaultImage;
            }

            // set default image for cases when original image is missing
            $productData['default_image'] = $defaultImage;

            $inventoryStatus = $productData['inventory_status'];
            $productData['inventory_status_label'] = $inventoryStatuses[$inventoryStatus] ?? $inventoryStatus;

            // product ID or SKU are not used as a key to keep proper order at the storefront
            $data[] = $productData;
        }

        return $data;
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
