<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Service;

use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Repository\ProductOptionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductOptionService
{
    const CODE = 'APOLLO';

    /**
     * @var ProductOptionRepositoryInterface
     */
    private ProductOptionRepositoryInterface $productOptionRepository;
    private FactoryInterface $productOptionFactory;
    private FactoryInterface $productOptionValueFactory;
    private TranslatorInterface $translator;
    private array $availableLocales;

    public function __construct(
        ProductOptionRepositoryInterface $productOptionRepository,
        FactoryInterface $productOptionFactory,
        FactoryInterface $productOptionValueFactory,
        LocaleProviderInterface $localeProvider,
        TranslatorInterface $translator
    )
    {
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->availableLocales = $localeProvider->getAvailableLocalesCodes();
        $this->translator = $translator;
    }

    public function initOptions(): void
    {
        /** @var ProductOptionInterface $productOption */
        $productOption = $this->productOptionRepository->findOneBy(['code' => 'DISTRIBUTORS']);


        if ($productOption) {
            if ($this->optionValueExists($productOption)) {
                return;
            }
            $this->createOptionValue($productOption);
        } else {
            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionFactory->createNew();

            foreach ($this->availableLocales as $locale) {
                $productOption->getTranslation($locale)->setName($this->translator->trans('printplius_sylius_apollo_integration.product_option.warehouses', [], null, $locale));
            }
            $productOption->setCode('DISTRIBUTORS');
            $productOption->setPosition(0);
            $this->createOptionValue($productOption);
        }

        $this->productOptionRepository->add($productOption);
    }

    private function createOptionValue(ProductOptionInterface $productOption): void
    {
        /** @var ProductOptionValueInterface $productOptionValue */
        $productOptionValue = $this->productOptionValueFactory->createNew();

        $productOptionValue->setCode(self::CODE);
        foreach ($this->availableLocales as $locale) {
            $productOptionValue->getTranslation($locale)->setValue(self::CODE);
        }
        $productOption->addValue($productOptionValue);
    }

    private function optionValueExists(ProductOptionInterface $productOption)
    {
        return $productOption->getValues()->exists(function ($key, ProductOptionValueInterface $value) {
            return $value->getCode() === 'APOLLO';
        });
    }
}
