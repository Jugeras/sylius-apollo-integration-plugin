<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Helper;

use Doctrine\ORM\EntityManagerInterface;
use PrintPlius\SyliusApolloIntegrationPlugin\Entity\ApolloShippingPricing;
use PrintPlius\SyliusApolloIntegrationPlugin\Service\ProductOptionService;
use PrintPlius\SyliusCarrierPlusPlugin\Interfaces\ShippingCalculationInterface;
use PrintPlius\SyliusIceCatIntegrationPlugin\Interfaces\CategoryAssociationInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;

class CategoryAssociationHelper implements CategoryAssociationInterface, ShippingCalculationInterface
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /** @param ProductInterface $product */
    static public function getSupplierCategoryName(ProductInterface $product): ?string
    {
        return null;
    }

    /** @param ProductInterface $product */
    static public function getTaxon(ProductInterface $product): ?TaxonInterface
    {
        return null;
    }

    public function getShippingPrice(float $weight): int
    {
        if (!$weight) {
            $weight = 1;
        }
        /** @var ?ApolloShippingPricing $shippingPricing */
        $shippingPricing = $this->entityManager->getRepository(ApolloShippingPricing::class)->findByWeight($weight);

        if (empty($shippingPricing)) {
            $price = 0;
        } else {
            $price = $shippingPricing->getPrice();
        }

        return $price;
    }

    public function getCode(): string
    {
        return ProductOptionService::CODE;
    }
}
