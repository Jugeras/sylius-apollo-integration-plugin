<?php

declare(strict_types=1);

namespace Tests\PrintPlius\SyliusApolloIntegrationPlugin\Application\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use PrintPlius\SyliusIceCatIntegrationPlugin\Entity\MeasureAwareInterface;
use PrintPlius\SyliusIceCatIntegrationPlugin\Entity\MeasureAwareTrait;
use Sylius\Component\Product\Model\ProductAttributeTranslation as BaseProductAttributeTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_attribute_translation")
 */
class ProductAttributeTranslation extends BaseProductAttributeTranslation implements MeasureAwareInterface
{
    use MeasureAwareTrait;
}
