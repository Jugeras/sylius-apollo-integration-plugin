<?php

declare(strict_types=1);

namespace Tests\PrintPlius\SyliusApolloIntegrationPlugin\Application\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use PrintPlius\SyliusB2BPlugin\Entity\Product\ExtraFieldsAwareInterface;
use PrintPlius\SyliusB2BPlugin\Entity\Product\ExtraFieldsAwareTrait;
use PrintPlius\SyliusIceCatIntegrationPlugin\Entity\IceCatAwareInterface;
use PrintPlius\SyliusIceCatIntegrationPlugin\Entity\IceCatAwareTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use Loevgaard\SyliusBrandPlugin\Model\ProductInterface as LoevgaardSyliusBrandPluginProductInterface;
use Loevgaard\SyliusBrandPlugin\Model\ProductTrait as LoevgaardSyliusBrandPluginProductTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product")
 */
class Product extends BaseProduct implements
    LoevgaardSyliusBrandPluginProductInterface,
    ExtraFieldsAwareInterface,
    IceCatAwareInterface
{
    use LoevgaardSyliusBrandPluginProductTrait;
    use ExtraFieldsAwareTrait;
    use IceCatAwareTrait;

    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }
}
