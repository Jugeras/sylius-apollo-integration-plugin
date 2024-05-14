<?php

declare(strict_types=1);

namespace Tests\PrintPlius\SyliusApolloIntegrationPlugin\Application\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use PrintPlius\SyliusB2BPlugin\Entity\Product\ArrivingAwareInterface;
use PrintPlius\SyliusB2BPlugin\Entity\Product\ArrivingAwareTrait;
use Sylius\Component\Core\Model\ProductVariant as BaseProductVariant;
use Sylius\Component\Product\Model\ProductVariantTranslation;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_variant")
 */
class ProductVariant extends BaseProductVariant implements ArrivingAwareInterface
{
    use ArrivingAwareTrait;

    protected function createTranslation(): ProductVariantTranslationInterface
    {
        return new ProductVariantTranslation();
    }
}
