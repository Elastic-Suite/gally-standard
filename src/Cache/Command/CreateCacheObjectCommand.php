<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Cache\Command;

use Gally\Cache\Service\CacheManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'gally:cache:create-cache-object')]
class CreateCacheObjectCommand extends Command
{
    public function __construct(private CacheManagerInterface $cache, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Create a cache object with a given key, value, tag(s) and ttl in gally cache pool.');
        $this
            ->setDefinition([
                new InputArgument('key', InputArgument::REQUIRED, 'The cache key of the object'),
                new InputArgument('value', InputArgument::REQUIRED, 'The cache value of the object'),
                new InputArgument('tags', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The cache tags to invalidate cache for'),
                new InputOption('ttl', null, InputOption::VALUE_REQUIRED, 'The TTL in seconds of the cache object'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> creates a cache object in the gally cache pool with the provided key, value and cache tag(s).

    %command.full_name% [--ttl=TTL] <key> <value> <tags>...
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheKey = $input->getArgument('key');
        $cacheValue = $input->getArgument('value');
        $cacheTags = $input->getArgument('tags');

        $cacheTtl = 0;
        if ($input->hasOption('ttl')) {
            $cacheTtl = (int) $input->getOption('ttl');
        }

        $storedValue = $this->cache->get($cacheKey, function (&$tags, &$ttl) use ($output, $cacheValue) {
            $output->writeln('(Re)Creating cache object.');
            if (empty($tags)) {
                $output->writeln('No tags, provided, adding the "no-tag" tag');
                $tags[] = 'no-tag';
            }
            if (empty($ttl)) {
                $output->writeln('No TTL provided, cache object will never expire');
            }

            return $cacheValue;
        }, $cacheTags, $cacheTtl);

        $output->writeln(\sprintf('Stored value at key "%s" is "%s"', $cacheKey, $cacheValue));

        return Command::SUCCESS;
    }
}
