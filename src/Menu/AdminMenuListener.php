<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    /**
     * @param MenuBuilderEvent $event
     */
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $integrations = $menu->getChild('integrations');
        if (!$integrations) {
            $this->createParentChild($menu);
            $integrations = $menu->getChild('integrations');
        }

        if ($integrations) {
            $this->addChild($integrations);
        } else {
            $this->addChild($menu->getLastChild());
        }
    }

    private function createParentChild(ItemInterface $item)
    {
        $item
            ->addChild('integrations')
            ->setLabel('printplius_sylius_apollo_integration.ui.integrations');
    }

    private function addChild(ItemInterface $item): void
    {
        $item
            ->addChild('apollo_integration', [
                'route' => 'printplius_sylius_apollo_integration_admin_index',
            ])
            ->setLabel('printplius_sylius_apollo_integration.ui.apollo_integration');
    }
}
