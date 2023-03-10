<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Example\Command\Cache;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @codeCoverageIgnore
 */
class InvalidateTagsInjectedCommand extends Command
{
    /** @var TagAwareCacheInterface[] */
    private array $pools;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'gally:example:cache-invalidate-tags-injected';

    public function __construct(
        array $pools = null,
        string $name = null
    ) {
        parent::__construct($name);
        $this->pools = $pools;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Invalidate tag(s) from constructor injected pools.');
        $this
            ->setDefinition([
                new InputArgument('tags', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The cache tags to invalidate cache for'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> invalidates cache objects from pools injected into the command constructor.

    %command.full_name% <key>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tags = $input->getArgument('tags');

        foreach ($this->pools as $pool) {
            $output->writeln(sprintf('Invalidating tags [%s] in pool of type %s', implode(', ', $tags), \get_class($pool)));

            if ($pool instanceof TagAwareCacheInterface) {
                try {
                    $pool->invalidateTags($tags);
                } catch (InvalidArgumentException $e) {
                    $output->writeln('Provided tags are invalid for this cache pool');
                }
            } else { // @phpstan-ignore-line
                $output->writeln(sprintf('Injected service %s is not a valid tag aware cache pool', \get_class($pool)));
            }
        }

        return Command::SUCCESS;
    }
}
