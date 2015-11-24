<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginTypeAnnotationCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginTypeAnnotationGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;

class PluginTypeAnnotationCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:type:annotation')
            ->setDescription($this->trans('commands.generate.plugin.type.annotation.description'))
            ->setHelp($this->trans('commands.generate.plugin.type.annotation.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.class-name')
            )
            ->addOption(
                'machine-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.plugin-id')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.label')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
        $machine_name = $input->getOption('machine-name');
        $label = $input->getOption('label');

        $generator = $this->getGenerator();
        $generator->generate($module, $class_name, $machine_name, $label);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --class-name option
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.type.annotation.options.class-name'),
                    'ExamplePlugin'
                ),
                'ExamplePlugin'
            );
        }
        $input->setOption('class-name', $class_name);

        $default_machine_name = $this->getStringHelper()->camelCaseToUnderscore($class_name);

        // --machine-name option
        $machine_name = $input->getOption('machine-name');
        if (!$machine_name) {
            $machine_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.type.annotation.options.machine-name'),
                    $default_machine_name
                ),
                $default_machine_name
            );
        }
        $input->setOption('machine-name', $machine_name);

        $default_label = $this->getStringHelper()->camelCaseToHuman($class_name);

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.type.annotation.options.label'),
                    $default_label
                ),
                $default_label
            );
        }
        $input->setOption('label', $label);
    }

    protected function createGenerator()
    {
        return new PluginTypeAnnotationGenerator();
    }
}
