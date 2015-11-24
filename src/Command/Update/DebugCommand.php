<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\DebugCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;

class DebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:debug')
            ->setDescription($this->trans('commands.update.debug.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $this->getTableHelper();
        $table->setlayout($table::LAYOUT_COMPACT);

        $this->getDrupalHelper()->loadLegacyFile('/core/includes/update.inc');
        $this->getDrupalHelper()->loadLegacyFile('/core/includes/install.inc');

        drupal_load_updates();
        update_fix_compatibility();

        $updates = update_get_update_list();
        $requirements = update_check_requirements();
        $severity = drupal_requirements_severity($requirements);

        if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING)) {
            $output->writeln(
                '[-] <info>' .
                $this->trans('commands.update.debug.messages.requirements-error')
                . '</info>'
            );

            $table->setHeaders(
                [
                    $this->trans('commands.update.debug.messages.severity'),
                    $this->trans('commands.update.debug.messages.title'),
                    $this->trans('commands.update.debug.messages.value'),
                    $this->trans('commands.update.debug.messages.description')
                ]
            );

            foreach ($requirements as $requirement) {
                if (isset($requirement['minimum schema']) & in_array($requirement['minimum schema'], array(REQUIREMENT_ERROR, REQUIREMENT_WARNING))) {
                    $table->addRow(
                        [
                                $requirement['severity'],
                                $requirement['title'],
                                $requirement['value'],
                                $requirement['description']
                            ]
                    );
                }
            }

            $table->render($output);
            return;
        }

        if (empty($updates)) {
            $output->writeln(
                '[-] <info>' .
                $this->trans('commands.update.debug.messages.no-updates')
                . '</info>'
            );
            return;
        }

        $table->setHeaders(
            [
                $this->trans('commands.update.debug.messages.module'),
                $this->trans('commands.update.debug.messages.update-n'),
                $this->trans('commands.update.debug.messages.description')
            ]
        );

        $output->writeln(
            '<info>'.
            $this->trans('commands.update.debug.messages.module-list')
            .'</info>'
        );


        foreach ($updates as $module => $module_updates) {
            foreach ($module_updates['pending'] as $update_n => $update) {
                list(, $description) = explode($update_n . " - ", $update);
                $table->addRow(
                    [
                        $module,
                        $update_n,
                        trim($description)
                    ]
                );
            }
        }

        $table->render($output);
    }
}
