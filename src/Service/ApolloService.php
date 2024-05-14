<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Orx;
use GuzzleHttp\Client;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PrintPlius\SyliusApolloIntegrationPlugin\Helper\CSVReaderHelper;
use PrintPlius\SyliusApolloIntegrationPlugin\Helper\ProductHelper;
use PrintPlius\SyliusB2BPlugin\Entity\Order\FailedOrderAwareInterface;
use PrintPlius\SyliusB2BPlugin\Entity\Product\ExtraFieldsAwareInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderShippingStates;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ApolloService
{

    private string $url;
    private string $projectDir;
    private Client $client;
    private FilesystemAdapter $cache;
    private EntityManagerInterface $entityManager;
    private ProductHelper $productHelper;
    private ?ChannelInterface $defaultChannel;
    private LoggerInterface $logger;
    private array $priority;

    public function __construct(
        string $url,
        array $priority,
        string $projectDir,
        EntityManagerInterface $entityManager,
        ProductOptionService $productOptionService,
        ProductHelper $productHelper,
        ChannelRepositoryInterface $channelRepository,
        LoggerInterface $logger
    )
    {
        if (empty($url)) {
            throw new \InvalidArgumentException(
                'Please define APOLLO_URL in .env file'
            );
        }

        $productOptionService->initOptions();

        $this->client = new Client([
            'http_errors' => false,
            'verify'      => false,
        ]);
        $this->url = $url;
        $this->projectDir = $projectDir;

        /** @var ChannelInterface|null defaultChannel */
        $this->defaultChannel = $channelRepository->findOneBy([
            'enabled' => true,
        ]);

        $this->cache = new FilesystemAdapter('apollo');
        $this->entityManager = $entityManager;
        $this->productHelper = $productHelper;
        $this->logger = $logger;
        $this->priority = $priority;
    }



    private function getProducts()
    {
        $filePath = $this->projectDir.'/var/uploads/apollo/apollo.xls';

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        $sheet = $spreadsheet->setActiveSheetIndex(0);

        $data = $this->formatData($sheet->toArray());

        foreach ($data as &$datum) {
            foreach ($datum as &$value) {
                if($value) {
                    $value = trim($value);
                }
            }
        }

        return $data;
    }

    public function importProducts()
    {
        $productsData = [];
        try {
            foreach ($this->getProducts() as $product) {
                $data = [];
                $data['manufacturer'] = (string)$product['brand'];
                $data['name'] = (string)$product['name'];
                $data['mpn'] = (string)$product['mpn'];
                $data['code'] = (string)$product['mpn'];
                $data['price'] = (float)$product['price'];
                $data['finalPrice'] = (float)$product['final_price'];
                $data['quantity'] = 0;
                $data['deliveryDate'] = '';
                $data['arrivingQuantity'] = '';
                $data['category'] = $this->getCategory((string)$product['category']);
                $data['images'] = [];
                $data['features'] = [];

                foreach ($product as $name => $value) {
                    if (strpos($name, 'image') !== false) {
                        $data['images'][] = $this->projectDir.'/var/uploads/apollo/images/'.$value;
                    }

                    if (strpos($name, 'feature') !== false) {
                        $exploded = explode('_', $name);

                        $data['features'][$exploded[1]] = $value;
                    }
                }

                $productsData[] = $data;
            }

            $this->productHelper->createProducts($productsData);

        } catch (\Throwable $e) {
            $this->logger->warning('[APOLLO] ' . $e->getMessage(), [
                'file'  => $e->getFile() . ' : ' . $e->getLine(),
                'trace' => $e->getTrace()
            ]);
        }
    }

    public function updateProducts()
    {
        $quantities = $this->getQuantities();
        $products = $this->getProducts();
        $codes = array_column($products, 'mpn');

        $productsData = [];
        foreach ($quantities as $quantity) {
            $mpn = rtrim($quantity['Part number']);

            if (!in_array($mpn, $codes)) continue;

            $productsData[] = [
                'mpn' => $mpn,
                'code' => $mpn,
                'quantity' => (int)$quantity['Quantity'],
                'ean' => (string)$quantity['EAN'],
            ];
        }

        $this->productHelper->updateProducts($productsData);
    }

    public function validateFile(FormInterface $form)
    {
        /** @var UploadedFile $file */
        $file = $form->get('excel')->getData();

        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getRealPath());

        $sheet = $spreadsheet->setActiveSheetIndex(0);

        $data = $sheet->toArray();

        if (!isset($data[1])) {
            $form->get('excel')->addError(new FormError('Bad excel format'));
            return false;
        }

        $formattedData = $this->formatData($data);

        foreach ($formattedData as $key => $formattedDatum) {
            if (empty($formattedDatum['mpn'])) {
                $form->get('excel')->addError(new FormError('Bad row '.($key+2).' mpn value is empty'));
            }
            if (empty($formattedDatum['name'])) {
                $form->get('excel')->addError(new FormError('Bad row '.($key+2).' name value is empty'));
            }
            if (empty($formattedDatum['category'])) {
                $form->get('excel')->addError(new FormError('Bad row '.($key+2).' category value is empty'));
            }
            if (empty($formattedDatum['final_price'])) {
                $form->get('excel')->addError(new FormError('Bad row '.($key+2).' final_price value is empty'));
            }
            if (empty($formattedDatum['price'])) {
                $form->get('excel')->addError(new FormError('Bad row '.($key+2).' price value is empty'));
            }
            if (empty($formattedDatum['brand'])) {
                $form->get('excel')->addError(new FormError('Bad row '.($key+2).' brand value is empty'));
            }
            if (!empty($formattedDatum['category']) && !$this->getCategory($formattedDatum['category'])) {
                $form->get('excel')->addError(new FormError('Bad row '.($key+2).' category does not exists'));
            }
        }

        foreach ($formattedData[0] as $name => $value) {
            if (strpos($name, 'feature_') !== false) {
                $exploded = explode('_', $name);

                if(!$this->getFeature($exploded[1])) {
                    $form->get('excel')->addError(new FormError('Bad '.$name.' feature does not exists'));
                }
            }
        }

        return $form->get('excel')->getErrors()->count() == 0;
    }

    private function formatData(array $data): array
    {
        unset($data[0]);

        $keys = null;

        $results = [];
        foreach ($data as $row) {
            if (empty($keys)) {
                $keys = $row;
                continue;
            }

            $results[] = array_combine($keys, $row);
        }

        return $results;
    }

    private function getCategory($category)
    {
        /** @var CacheItem $products */
        $taxon = $this->cache->getItem('taxon_' . $category);

        if (!$taxon->isHit()) {
            $taxon->set($this->entityManager->getRepository(TaxonInterface::class)->findOneBy(['code' => $category]));
            $this->cache->save($taxon);
        }

        return $taxon->get();
    }

    private function getFeature($feature)
    {
        /** @var CacheItem $products */
        $attribute = $this->cache->getItem('attribute_' . $feature);

        if (!$attribute->isHit()) {
            $attribute->set($this->entityManager->getRepository(AttributeInterface::class)->findOneBy(['code' => $feature]));
            $this->cache->save($attribute);
        }

        return $attribute->get();
    }

    private function getQuantities()
    {
        try {
            $content = file_get_contents($this->url);

            $tmpFileName = tempnam(sys_get_temp_dir(), 'csv');
            $temp = fopen($tmpFileName, 'w');
            fwrite($temp, $content);
            fclose($temp);
            unset($content);

            return (new CSVReaderHelper($tmpFileName, ';'))->getData();

        } catch (\Throwable $e) {
            $this->logger->warning('[APOLLO] ' . $e->getMessage(), [
                'file'  => $e->getFile() . ' : ' . $e->getLine(),
                'trace' => $e->getTrace()
            ]);
        }
    }
}
