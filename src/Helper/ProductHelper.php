<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Helper;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Loevgaard\SyliusBrandPlugin\Doctrine\ORM\BrandRepository;
use Loevgaard\SyliusBrandPlugin\Doctrine\ORM\BrandRepositoryInterface;
use Loevgaard\SyliusBrandPlugin\Model\Brand;
use Loevgaard\SyliusBrandPlugin\Model\BrandInterface;
use PrintPlius\SyliusApolloIntegrationPlugin\Entity\ApolloDuplicate;
use PrintPlius\SyliusApolloIntegrationPlugin\Repository\ApolloDuplicateRepository;
use PrintPlius\SyliusApolloIntegrationPlugin\Service\ProductOptionService;
use PrintPlius\SyliusB2BPlugin\Entity\Product\ExtraFieldsAwareInterface;
use PrintPlius\SyliusIceCatIntegrationPlugin\Entity\AttributeGroup;
use PrintPlius\SyliusIceCatIntegrationPlugin\Model\SelectAttributeConfiguration;
use PrintPlius\SyliusIceCatIntegrationPlugin\Repository\AttributeGroupRepository;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxation\Model\TaxCategoryInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductHelper
{
    public static $UPDATE_ES = true;

    private LoggerInterface $logger;
    private ProductRepositoryInterface $productRepository;
    private ProductFactoryInterface $productFactory;
    private array $availableLocales;
    private SlugGeneratorInterface $slugGenerator;
    private ProductVariantRepositoryInterface $productVariantRepository;
    private ProductVariantFactoryInterface $productVariantFactory;
    private ?ChannelInterface $defaultChannel;
    private EntityRepository $channelPricingRepository;
    private FactoryInterface $channelPricingFactory;
    private ImageUploaderInterface $imageUploader;
    private FactoryInterface $productTaxonFactory;
    private ?ProductOptionInterface $productOption;
    public ?ProductOptionValueInterface $productOptionValue;
    private FactoryInterface $productImageFactory;
    private BrandRepositoryInterface $brandRepository;
    private TaxonRepositoryInterface $taxonRepository;
    private string $brandClass;
    private TaxonFactoryInterface $taxonFactory;
    private EntityManagerInterface $entityManager;
    private EntityRepository $productAttributeRepository;
    private FactoryInterface $productAttributeFactory;
    private FactoryInterface $productAttributeValueFactory;
    private AttributeGroupRepository $attributeGroupRepository;
    private ApolloDuplicateRepository $apolloDuplicateRepository;

    public function __construct(
        LoggerInterface                   $logger,
        ProductFactoryInterface           $productFactory,
        LocaleProviderInterface           $localeProvider,
        SlugGeneratorInterface            $slugGenerator,
        ProductVariantFactoryInterface    $productVariantFactory,
        FactoryInterface                  $channelPricingFactory,
        ImageUploaderInterface            $imageUploader,
        FactoryInterface                  $productTaxonFactory,
        FactoryInterface                  $productImageFactory,
        TaxonFactoryInterface             $taxonFactory,
        EntityManagerInterface            $entityManager,
        FactoryInterface                  $productAttributeFactory,
        FactoryInterface                  $productAttributeValueFactory,
        string                            $brandClass
    )
    {
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->availableLocales = $localeProvider->getAvailableLocalesCodes();
        $this->slugGenerator = $slugGenerator;
        $this->productVariantFactory = $productVariantFactory;
        $this->channelPricingFactory = $channelPricingFactory;
        $this->imageUploader = $imageUploader;
        $this->productTaxonFactory = $productTaxonFactory;
        $this->productImageFactory = $productImageFactory;
        $this->taxonFactory = $taxonFactory;
        $this->entityManager = $entityManager;
        $this->brandClass = $brandClass;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->productAttributeFactory = $productAttributeFactory;

        // Recreate
        $this->productRepository = $this->entityManager->getRepository(ProductInterface::class);
        $this->productVariantRepository = $this->entityManager->getRepository(ProductVariantInterface::class);
        $this->channelPricingRepository = $this->entityManager->getRepository(ChannelPricingInterface::class);
        $this->brandRepository = $this->entityManager->getRepository(Brand::class);
        $this->taxonRepository = $this->entityManager->getRepository(TaxonInterface::class);
        $this->productAttributeRepository = $this->entityManager->getRepository(ProductAttributeInterface::class);
        $this->attributeGroupRepository = $this->entityManager->getRepository(AttributeGroup::class);
        $this->productAttributeValueRepository = $this->entityManager->getRepository(ProductAttributeValueInterface::class);
        $this->apolloDuplicateRepository = $this->entityManager->getRepository(ApolloDuplicate::class);

        $this->defaultChannel = $this->entityManager->getRepository(ChannelInterface::class)->findOneBy([
            'enabled' => true,
        ]);
        $this->productOptionValue = $this->entityManager->getRepository(ProductOptionValueInterface::class)->findOneBy(['code' => ProductOptionService::CODE]);
        if ($this->productOptionValue) {
            $this->productOption = $this->productOptionValue->getOption();
        }

        $this->defaultTaxCategory = $this->entityManager->getRepository(TaxCategoryInterface::class)->findOneBy([]);
    }

    private function resetEM()
    {
        if (!$this->entityManager->isOpen()) {
            $this->entityManager = $this->entityManager->create(
                $this->entityManager->getConnection(),
                $this->entityManager->getConfiguration()
            );
        }

        // Recreate
        $this->productRepository = $this->entityManager->getRepository(ProductInterface::class);
        $this->productVariantRepository = $this->entityManager->getRepository(ProductVariantInterface::class);
        $this->channelPricingRepository = $this->entityManager->getRepository(ChannelPricingInterface::class);
        $this->brandRepository = $this->entityManager->getRepository(Brand::class);
        $this->taxonRepository = $this->entityManager->getRepository(TaxonInterface::class);
        $this->productAttributeRepository = $this->entityManager->getRepository(ProductAttributeInterface::class);
        $this->attributeGroupRepository = $this->entityManager->getRepository(AttributeGroup::class);
        $this->productAttributeValueRepository = $this->entityManager->getRepository(ProductAttributeValueInterface::class);
        $this->apolloDuplicateRepository = $this->entityManager->getRepository(ApolloDuplicate::class);
        $this->attributeGroupRepository = $this->entityManager->getRepository(AttributeGroup::class);

        $this->defaultChannel = $this->entityManager->getRepository(ChannelInterface::class)->findOneBy([
            'enabled' => true,
        ]);
        $this->productOptionValue = $this->entityManager->getRepository(ProductOptionValueInterface::class)->findOneBy(['code' => ProductOptionService::CODE]);
        if ($this->productOptionValue) {
            $this->productOption = $this->productOptionValue->getOption();
        }

        $this->defaultTaxCategory = $this->entityManager->getRepository(TaxCategoryInterface::class)->findOneBy([]);
    }

    public function createProducts(array $productsData)
    {
        $iteration = 0;

        foreach ($productsData as $productsDatum) {
            $iteration++;

            try {
                $product = $this->getProduct($productsDatum['mpn']);

                //CHECK MANUFACTURER
                if (class_exists(\App\Utils\ManufacturerDuplicateTrait::class) && isset($productsDatum['manufacturer'])) {
                    $productsDatum['manufacturer'] = \App\Utils\ManufacturerDuplicateTrait::getManufacturer($productsDatum['manufacturer']);

                    if ($product->getBrand()) {
                        $productBrand = \App\Utils\ManufacturerDuplicateTrait::getManufacturer($product->getBrand()->getName());
                    }
                }

                if (
                    $product->getId() &&
                    !empty($productsDatum['manufacturer']) &&
                    $product->getBrand() &&
                    ($productBrand ?? rtrim(strtolower($product->getBrand()->getName()))) != $productsDatum['manufacturer']
                ) {
                    $this->processDuplicate($product, $productsDatum);
                }

                if (!$product->getId()) {
                    $this->setDetails($product, $productsDatum);
                    $this->setVariant($product, $productsDatum);
                    $this->setTaxons($product, $productsDatum);
                    $this->setImage($product, $productsDatum);
                    $this->setManufacturer($product, $productsDatum);

                    $this->setAttributes($product, $productsDatum);
                } else {
                    $this->setDetails($product, $productsDatum);
                    $this->setVariant($product, $productsDatum);
                    $this->setManufacturer($product, $productsDatum);
                }

                $product->setEnabled(false);
                if (
                    $product->getMainTaxon() &&
                    $product->getMainTaxon()->getCode() !== 'IMPORT' &&
                    $product->getMainTaxon()->getCode() !== 'PROCESSED'
                ) {
                    $product->setEnabled(true);
                }
                $this->productRepository->createQueryBuilder('p')
                    ->update()
                    ->set('p.updatedAt', ':time')
                    ->where('p.id = :id')
                    ->setParameter('time', new \DateTime())
                    ->setParameter('id', $product->getId())
                    ->getQuery()
                    ->execute();


                $this->entityManager->persist($product);

                if ($iteration % 500 == 0) {
                    $this->entityManager->flush();
                }
            } catch (\Throwable $e) {
                $this->logger->warning('[APOLLO] ' . $e->getMessage(), [
                    'productDatum' => $productsDatum,
                    'file'         => $e->getFile() . ' : ' . $e->getLine(),
                    'trace'        => $e->getTrace()
                ]);
                $this->resetEM();
            }

        }
        $this->entityManager->flush();
    }

    public function updateProducts(array $productsData)
    {
        $iteration = 0;

        foreach ($productsData as $productsDatum) {
            $iteration++;

            try {
                $product = $this->getProduct($productsDatum['mpn']);

                $product->addEan13($productsDatum['ean']);
                $this->setVariant($product, $productsDatum);

                $product->setEnabled(false);
                if (
                    $product->getMainTaxon() &&
                    $product->getMainTaxon()->getCode() !== 'IMPORT' &&
                    $product->getMainTaxon()->getCode() !== 'PROCESSED'
                ) {
                    $product->setEnabled(true);
                }
                $this->productRepository->createQueryBuilder('p')
                    ->update()
                    ->set('p.updatedAt', ':time')
                    ->where('p.id = :id')
                    ->setParameter('time', new \DateTime())
                    ->setParameter('id', $product->getId())
                    ->getQuery()
                    ->execute();


                $this->entityManager->persist($product);

                if ($iteration % 500 == 0) {
                    $this->entityManager->flush();
                }
            } catch (\Throwable $e) {
                $this->logger->warning('[APOLLO] ' . $e->getMessage(), [
                    'productDatum' => $productsDatum,
                    'file'         => $e->getFile() . ' : ' . $e->getLine(),
                    'trace'        => $e->getTrace()
                ]);
                $this->resetEM();
            }

        }
        $this->entityManager->flush();
    }

    private function getProduct(string $code): ProductInterface
    {
        /** @var ProductInterface|null $product */
        $product = $this->entityManager->getRepository(ProductInterface::class)->findOneBy(['code' => $code]);

        if ($product === null) {
            /** @var ProductInterface $product */
            $product = $this->productFactory->createNew();
            $product->setCode($code);
        } else {
            $this->entityManager->refresh($product);
        }

        return $product;
    }

    /** @param ProductInterface|ExtraFieldsAwareInterface $product */
    private function setDetails(ProductInterface $product, array $productsDatum): void
    {
        if (method_exists($product, 'getImportDescription') && !$product->getImportDescription()) {
            return;
        }

        foreach ($this->availableLocales as $locale) {
            $product->getTranslation($locale)->setName(substr($productsDatum['name'], 0, 255));
            $product->getTranslation($locale)->setDescription($productsDatum['longDescription'] ?? null);
            $product->getTranslation($locale)->setShortDescription($productsDatum['shortDescription'] ?? null);

            if (empty($product->getTranslation($locale)->getSlug())) {
                $product->getTranslation($locale)->setSlug(
                    $this->generateSlug($productsDatum['mpn'] . '-' . $product->getName())
                );
            }
        }
        $product->setEnabled(false);
        $product->addChannel($this->entityManager->getReference(ChannelInterface::class, $this->defaultChannel->getId()));
        $product->setVariantSelectionMethod(ProductInterface::VARIANT_SELECTION_CHOICE);
        $product->addOption($this->entityManager->getReference(ProductOptionInterface::class, $this->productOption->getId()));
    }

    private function setVariant(ProductInterface $product, array $productsDatum): void
    {
        $idOption = $this->productOptionValue->getId();
        $productVariant = $this->getProductVariant($idOption . '@@' . $productsDatum['code']);

        foreach ($this->availableLocales as $locale) {
            $productVariant->getTranslation($locale)->setName($product->getName());
        }

        $productVariant->setEnabled(true);
        $productVariant->setTaxCategory($this->entityManager->getReference(TaxCategoryInterface::class, $this->defaultTaxCategory->getId()));
        $productVariant->addOptionValue($this->entityManager->getReference(ProductOptionValueInterface::class, $this->productOptionValue->getId()));

        $productVariant->setDepth($productsDatum['depth'] ?? $productVariant->getDepth());
        $productVariant->setHeight($productsDatum['height'] ?? $productVariant->getHeight());
        $productVariant->setWeight($productsDatum['weight'] ?? $productVariant->getWeight());
        $productVariant->setWidth($productsDatum['width'] ?? $productVariant->getWidth());

        $productVariant->setTracked(true);
        $productVariant->setOnHand((int)$productsDatum['quantity']);

        if ($productVariant->getOnHand() <= 0 && $productVariant->getOnHold() > 0) {
            $productVariant->setOnHand($productVariant->getOnHold());
        }

        if (method_exists($productVariant, 'setBrand') && method_exists($productVariant, 'getBrand')) {
            $productVariant->setBrand($productsDatum['manufacturer'] ?? $productVariant->getBrand());
        }

        if (isset($productsDatum['price'])) {

            /** @var ChannelPricingInterface|null $channelPricing */
            $channelPricing = $this->channelPricingRepository->findOneBy([
                'channelCode'    => $this->defaultChannel->getCode(),
                'productVariant' => $productVariant
            ]);

            if ($channelPricing === null) {
                /** @var ChannelPricingInterface $channelPricing */
                $channelPricing = $this->channelPricingFactory->createNew();
                $channelPricing->setChannelCode($this->defaultChannel->getCode());
                $productVariant->addChannelPricing($channelPricing);
            }

            $channelPricing->setOriginalPrice(0);

            $channelPricing->setPrice((int)($productsDatum['price'] * 100));

            if (class_exists(\PrintPlius\SyliusDiscountPlugin\Entity\Discount::class)) {
                $discount = $this->entityManager->getRepository(\PrintPlius\SyliusDiscountPlugin\Entity\Discount::class)->findOneBy([
                    'productVariant' => $productVariant,
                    'validFrom'      => new \DateTime('2000-01-01 00:00:00'),
                    'validTo'        => new \DateTime('2999-12-31 23:59:59'),
                ]);

                if (empty($discount)) {
                    $discount = new \PrintPlius\SyliusDiscountPlugin\Entity\Discount();
                    $discount->setProductVariant($productVariant);
                    $discount->setValidFrom(new \DateTime('2000-01-01 00:00:00'));
                    $discount->setValidTo(new \DateTime('2999-12-31 23:59:59'));
                    $discount->setProduct($product);
                    $discount->setFromQuantity(0);
                }

                $discount->setPrice((int)($productsDatum['finalPrice'] * 100));

                $this->entityManager->persist($discount);
            }
        }


        if (!$productVariant->getId() || !$productVariant->getProduct() || $productVariant->getProduct()->getId() !== $product->getId()) {
            $product->addVariant($productVariant);
        }

        $this->productVariantRepository->createQueryBuilder('p')
            ->update()
            ->set('p.updatedAt', ':time')
            ->where('p.id = :id')
            ->setParameter('time', new \DateTime())
            ->setParameter('id', $productVariant->getId())
            ->getQuery()
            ->execute();
    }

    private function getProductVariant(string $code): ProductVariantInterface
    {
        /** @var ProductVariantInterface $productVariant */
//        $productVariant = $this->productVariantRepository->findOneBy(['code' => $code]);
        $productVariant = $this->entityManager->getRepository(ProductVariantInterface::class)->findOneBy(['code' => $code]);
        if ($productVariant === null) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();
            $productVariant->setCode($code);
        } else {
            $this->entityManager->refresh($productVariant);
        }

        return $productVariant;
    }

    private function generateCodeFromText(string $text)
    {
        $text = str_replace(['®', '™', '©'], '', $text);

        return preg_replace('~([^a-žA-Ž0-9])~', '_', strtolower($text));
    }


    private function setTaxons(ProductInterface $product, array $productsDatum): void
    {
        /** @var Collection|TaxonInterface[] $taxons */
        $taxons = $productsDatum['category']->getAncestors();
        $taxons[] = $productsDatum['category'];

        foreach ($taxons as $taxon) {
            /** @var ProductTaxonInterface $productTaxon */
            $productTaxon = $this->productTaxonFactory->createNew();
            $productTaxon->setTaxon($this->entityManager->getReference(TaxonInterface::class, $taxon->getId()));

            $product->addProductTaxon($productTaxon);
        }

        $product->setMainTaxon($this->entityManager->getReference(TaxonInterface::class, $productsDatum['category']->getId()));
    }

    private function setImage(ProductInterface $product, $productsDatum)
    {
        $filesystem = new Filesystem();
        if (!$filesystem->exists('/tmp/photos')) {
            $filesystem->mkdir('/tmp/photos');
        }

        if ($product->hasImages()) {
            return;
        }

        foreach ($productsDatum['images'] ?? [] as $index => $image) {
            try {
                if ($this->isImage($image)) {
                    $imageLink = $image;
                } else {
                    continue;
                }

                $file = file_get_contents($imageLink);
            } catch (\Throwable $e) {
                continue;
            }

            file_put_contents('/tmp/photos/apollo_image.tmp', $file);
            $uploadedImage = new UploadedFile(
                '/tmp/photos/apollo_image.tmp',
                $productsDatum['mpn'] . '_' . $index . '.jpg'
            );

            $productImage = $this->productImageFactory->createNew();
            $productImage->setType('main');
            $productImage->setFile($uploadedImage);
            $this->imageUploader->upload($productImage);

            $product->addImage($productImage);

            $this->entityManager->persist($product);
            unlink('/tmp/photos/apollo_image.tmp');
        }
    }

    private function isImage($path)
    {
        $a = @getimagesize($path);
        if ($a) {
            $image_type = $a[2];

            if (in_array($image_type, array(IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
                return true;
            }
        }
        return false;
    }


    /** @param ProductInterface|\Loevgaard\SyliusBrandPlugin\Model\ProductInterface $product */
    private function setManufacturer(ProductInterface $product, $productsDatum)
    {
        if (empty($productsDatum['manufacturer'])) return;

        $brand = $this->getBrandByName($productsDatum['manufacturer']);
        $product->setBrand($brand);
        $brand->addProduct($product);

    }

    private function getBrandByName($name)
    {
        /** @var BrandRepository $brandRepository */
        $brandRepository = $this->brandRepository;

        $brand = $brandRepository->createQueryBuilder('b')
            ->where('b.name LIKE :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($brand) {
            return $brand;
        }

        /** @var BrandInterface $brand */
        $brand = new $this->brandClass();
        $brand->setName(ucfirst($name));
        $brand->setCode($this->generateCodeFromText($name));

        $this->entityManager->persist($brand);
        $this->entityManager->flush();

        return $brand;
    }

    private function generateSlug($value)
    {
        $slug = $this->slugGenerator->generate($value);
        $index = 0;
        do {
            $slugTemp = substr($slug, 0, 250);
            if ($index) {
                $slugTemp .= '-' . $index;
            }

            /** @var QueryBuilder $qb */
            $qb = $this->productRepository->createQueryBuilder('p');
            $product = $qb
                ->innerJoin('p.translations', 'pt')
                ->orWhere('pt.slug = :slug')
                ->setParameter('slug', $slugTemp)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $index++;
        } while ($product);

        return $slugTemp;
    }

    private function setAttributes(ProductInterface $product, array $productsDatum): void
    {
        foreach ($productsDatum['features'] ?? [] as $attributeCode => $value) {

            /** @var ?ProductAttributeInterface $attribute */
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeCode]);

            foreach ($this->availableLocales as $locale) {
                if ($attribute->getType() == 'select') {
                    $selectAttributeConfiguration = new SelectAttributeConfiguration();
                    $selectAttributeConfiguration->__unserialize($attribute->getConfiguration());

                    $codes = [];
                    foreach ($selectAttributeConfiguration->getChoices() as $code => $values) {
                        if (in_array($value, $values)) {
                            $codes[] = $code;
                            break;
                        }
                    }
                    if (empty($codes)) {

                        $selectAttributeConfiguration->addChoice(
                            $this->generateCodeFromText($value),
                            $value,
                            $locale
                        );
                        $attribute->setConfiguration($selectAttributeConfiguration->__serialize());
                        $codes[] = $this->generateCodeFromText($value);
                    }


                    $attributeValue = $this->getProductAttributeValue($product, $attribute, $locale);
                    $attributeValue->setAttribute($attribute);
                    $attributeValue->setLocaleCode($locale);
                    $attributeValue->setValue($codes);
                    $product->addAttribute($attributeValue);
                } else {
                    $attributeValue = $this->getProductAttributeValue($product, $attribute, $locale);
                    $attributeValue->setAttribute($attribute);
                    $attributeValue->setLocaleCode($locale);

                    switch ($attribute->getStorageType()) {
                        case 'text':
                            $attributeValue->setValue(strip_tags($value));
                            break;
                        case 'float':
                            $attributeValue->setValue((float)$value);
                            break;
                        case 'boolean':
                            $attributeValue->setValue((bool)$value);
                            break;
                        case 'integer':
                            $attributeValue->setValue((int)$value);
                            break;
                    }
                    $product->addAttribute($attributeValue);
                }
            }
        }
    }

    private function getProductAttributeValue(ProductInterface $product, AttributeInterface $attribute, string $localeCode): object
    {
        $productAttributeValue = $this->productAttributeValueRepository->findOneBy([
            'subject' => $product->getId(),
            'attribute' => $attribute->getId(),
            'localeCode' => $localeCode
        ]);

        if ($productAttributeValue) {
            return $productAttributeValue;
        }

        return $this->productAttributeValueFactory->createNew();
    }

    /** @param ProductInterface|\Loevgaard\SyliusBrandPlugin\Model\ProductInterface $product */
    private function processDuplicate(ProductInterface &$product, array &$productsDatum)
    {
        /** @var null|ApolloDuplicate $duplicate */
        $duplicate = $this->apolloDuplicateRepository->findOneBy([
            'code'               => $productsDatum['mpn'],
            'importManufacturer' => $productsDatum['manufacturer']
        ]);

        if (empty($duplicate)) {
            $duplicate = new ApolloDuplicate();
            $duplicate->setCode($productsDatum['mpn']);
            $duplicate->setImportManufacturer($productsDatum['manufacturer']);
            $duplicate->setProductManufacturer($product->getBrand()->getName());

            $this->entityManager->persist($duplicate);
            $productsDatum['quantity'] = 0;
            $productsDatum['name'] = $product->getName();
            $productsDatum['manufacturer'] = $product->getBrand()->getName();
        } else {
            if (!empty($duplicate->getNewCode())) {
                $productsDatum['mpn'] = $duplicate->getNewCode();
                $product = $this->getProduct($productsDatum['mpn']);
            }
            if (!empty($duplicate->getNewManufacturer())) {
                $productsDatum['manufacturer'] = $duplicate->getNewManufacturer();
            }
            if (empty($duplicate->getNewCode()) && empty($duplicate->getNewManufacturer())) {
                $productsDatum['quantity'] = 0;
                $productsDatum['name'] = $product->getName();
                $productsDatum['manufacturer'] = $product->getBrand()->getName();
            }
        }
    }
}
