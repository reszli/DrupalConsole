<?php

/**
 * @file
 * Contains \Drupal\Console\Command\CacheRebuildCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheRebuildCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:rebuild')
            ->setDescription($this->trans('commands.cache.rebuild.description'))
            ->addArgument(
                'cache',
                InputArgument::OPTIONAL,
                $this->trans('commands.cache.rebuild.options.cache'),
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getDrupalHelper()->loadLegacyFile('/core/includes/utility.inc');

        $validators = $this->getValidator();

        // Get the --cache option and make validation
        $cache = $input->getArgument('cache');
        $validated_cache = $validators->validateCache($cache);
        if (!$validated_cache) {
            throw new \InvalidArgumentException(
                sprintf(
                    $this->trans('commands.cache.rebuild.messages.invalid_cache'),
                    $cache
                )
            );
        }

        // Start rebuilding cache
        $output->writeln('');
        $output->writeln('[+] <comment>'.$this->trans('commands.cache.rebuild.messages.rebuild').'</comment>');

        // Get data needed to rebuild cache
        $kernelHelper = $this->getKernelHelper();
        $classLoader = $kernelHelper->getClassLoader();
        $request = $kernelHelper->getRequest();

        // Check cache to rebuild
        if ($cache === 'all') {
            // If cache is all, then clear all caches
            drupal_rebuild($classLoader, $request);
        } else {
            // Else, clear the selected cache
            $caches = $validators->getCaches();
            $caches[$cache]->deleteAll();
        }

        // Finish rebuilding cache
        $output->writeln('[+] <info>'.$this->trans('commands.cache.rebuild.messages.completed').'</info>');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // Get the cache option
        $cache = $this->getCacheOption($input, $output, $dialog);
        $input->setArgument('cache', $cache);
    }

    private function getCacheOption($input, $output, $dialog)
    {
        $validators = $this->getValidator();

        // Get the --cache option and make user interaction with validation
        $cache = $input->getArgument('cache');
        if (!$cache) {
            $caches = $validators->getCaches();
            $cache_keys = array_keys($caches);
            $cache_keys[] = 'all';

            $cache = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.cache.rebuild.questions.cache'), 'all'),
                function ($cache) use ($validators) {
                    $validated_cache = $validators->validateCache($cache);
                    if (!$validated_cache) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.cache.rebuild.messages.invalid_cache'),
                                $cache
                            )
                        );
                    }

                    return $validated_cache;
                },
                false,
                'all',
                $cache_keys
            );
        }

        return $cache;
    }
}
