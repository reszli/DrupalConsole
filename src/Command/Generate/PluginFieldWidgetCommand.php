<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldWidgetCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginFieldWidgetGenerator;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;

class PluginFieldWidgetCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldwidget')
            ->setDescription($this->trans('commands.generate.plugin.fieldwidget.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldwidget.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldwidget.options.class-name')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.plugin-id')
            )
            ->addOption(
                'field-type',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.field-type')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmationQuestion
        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $field_type = $input->getOption('field-type');

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $field_type);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
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
                    $this->trans('commands.generate.plugin.fieldwidget.questions.class-name'),
                    'ExampleFieldWidget'
                ),
                'ExampleFieldWidget'
            );
        }
        $input->setOption('class-name', $class_name);

        $default_label = $this->getStringHelper()->camelCaseToHuman($class_name);

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.fieldwidget.questions.label'), $default_label),
                $default_label
            );
        }
        $input->setOption('label', $label);

        $machine_name = $this->getStringHelper()->camelCaseToUnderscore($class_name);

        // --name option
        $plugin_id = $input->getOption('plugin-id');

        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldwidget.questions.plugin-id'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('plugin-id', $plugin_id);

        // --field type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldwidget.questions.field-type'),
                    ''
                ),
                ''
            );
        }
        $input->setOption('field-type', $field_type);
    }

    protected function createGenerator()
    {
        return new PluginFieldWidgetGenerator();
    }
}
