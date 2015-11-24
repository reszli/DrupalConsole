<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRulesActionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginRulesActionGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;

class PluginRulesActionCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:rulesaction')
            ->setDescription($this->trans('commands.generate.plugin.rulesaction.description'))
            ->setHelp($this->trans('commands.generate.plugin.rulesaction.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.class-name')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.plugin-id')
            )
            ->addOption('type', '', InputOption::VALUE_REQUIRED, $this->trans('commands.generate.plugin.rulesaction.options.type'))
            ->addOption(
                'category',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rulesaction.options.category')
            )
            ->addOption(
                'context',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.context')
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
        $type = $input->getOption('type');
        $category = $input->getOption('category');
        $context = $input->getOption('context');

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $category, $context, $type);

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
                    $this->trans('commands.generate.plugin.rulesaction.options.class-name'),
                    'DefaultAction'
                ),
                'DefaultAction'
            );
        }
        $input->setOption('class-name', $class_name);

        $default_label = $this->getStringHelper()->camelCaseToHuman($class_name);

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.rulesaction.options.label'), $default_label),
                $default_label
            );
        }
        $input->setOption('label', $label);

        $machine_name = $this->getStringHelper()->camelCaseToUnderscore($class_name);

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.rulesaction.options.plugin-id'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('plugin-id', $plugin_id);

        // --type option
        $type = $input->getOption('type');
        if (!$type) {
            $type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.rulesaction.options.type'),
                    'user'
                ),
                'user'
            );
        }
        $input->setOption('type', $type);

        // --category option
        $category = $input->getOption('category');
        if (!$category) {
            $category = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.rulesaction.options.category'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('category', $category);

        // --context option
        $context = $input->getOption('context');
        if (!$context) {
            $context = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.rulesaction.options.context'), $machine_name),
                $machine_name
            );
        }
        $input->setOption('context', $context);
    }

    protected function createGenerator()
    {
        return new PluginRulesActionGenerator();
    }
}
