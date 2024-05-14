<?php

declare(strict_types=1);

namespace Tests\PrintPlius\SyliusApolloIntegrationPlugin\Application\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use PrintPlius\SyliusIceCatIntegrationPlugin\Entity\AttributeGroupAwareInterface;
use PrintPlius\SyliusIceCatIntegrationPlugin\Entity\AttributeGroupAwareTrait;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Product\Model\ProductAttribute as BaseProductAttribute;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_attribute")
 */
class ProductAttribute extends BaseProductAttribute implements AttributeGroupAwareInterface
{
    use AttributeGroupAwareTrait;

    protected function createTranslation(): AttributeTranslationInterface
    {
        return new ProductAttributeTranslation();
    }
}
