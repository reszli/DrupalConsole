<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\ExecuteCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;

class ExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:execute')
            ->setDescription($this->trans('commands.cron.execute.description'))
            ->addArgument('module', InputArgument::REQUIRED, $this->trans('commands.common.options.module'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');

        $module_handler = $this->getModuleHandler();

        if ($module != 'all') {
            $modules = [$module];
        } else {
            $modules = $module_handler->getImplementations('cron');
        }


        foreach ($modules as $module) {
            if ($module_handler->implementsHook($module, 'cron')) {
                $output->writeln(
                    '[-] <info>' .
                    sprintf(
                        $this->trans('commands.cron.execute.messages.executing-cron'),
                        $module
                    )
                    . '</info>'
                );
                try {
                    $module_handler->invoke($module, 'cron');
                } catch (\Exception $e) {
                    watchdog_exception('cron', $e);
                    $output->writeln(
                        '<error>' .
                        $e->getMessage() .
                        '</error>'
                    );
                }
            } else {
                $output->writeln(
                    '<error>' .
                    sprintf(
                        $this->trans('commands.cron.execute.messages.module-invalid'),
                        $module
                    )
                    . '</error>'
                );
            }
        }

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
