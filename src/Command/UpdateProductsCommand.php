<?php
declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Command;

use PrintPlius\SyliusApolloIntegrationPlugin\Service\ApolloService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

class UpdateProductsCommand extends Command
{
    use LockableTrait;
    const SUCCESS = 0;

    protected static $defaultName = 'apollo:update-product';
    private ApolloService $apolloService;

    public function __construct($name = null, ApolloService $apolloService)
    {
        parent::__construct($name);
        $this->apolloService = $apolloService;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return self::SUCCESS;
        }

        $this->apolloService->updateProducts();

        $this->release();
        return self::SUCCESS;
    }

    private function lock(string $name = null, bool $blocking = false): bool
    {
        if (!class_exists(SemaphoreStore::class)) {
            throw new \LogicException('To enable the locking feature you must install the symfony/lock component.');
        }

        if (null !== $this->lock) {
            throw new \LogicException('A lock is already in place.');
        }

        if (SemaphoreStore::isSupported()) {
            $store = new SemaphoreStore();
        } else {
            $store = new FlockStore();
        }

        $this->lock = (new LockFactory($store))->createLock($name ?: $this->getName(),43200 ); // 12h
        if (!$this->lock->acquire($blocking)) {
            $this->lock = null;

            return false;
        }

        return true;
    }
}
