<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginBlockCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginBlockGenerator;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;

class PluginBlockCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:block')
            ->setDescription($this->trans('commands.generate.plugin.block.description'))
            ->setHelp($this->trans('commands.generate.plugin.block.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.class-name')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.plugin-id')
            )
            ->addOption(
                'theme-region',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.theme-region')
            )
            ->addOption(
                'inputs',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.inputs')
            )
            ->addOption('services', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.common.options.services'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $message = $this->getMessageHelper();

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmationQuestion
        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $services = $input->getOption('services');
        $theme_region = $input->getOption('theme-region');
        $inputs = $input->getOption('inputs');

        $configFactory = $this->getConfigFactory();
        $theme = $configFactory->get('system.theme')->get('default');
        $theme_regions = system_region_list($theme, REGIONS_VISIBLE);


        if (!empty($theme_region) && !isset($theme_regions[$theme_region])) {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.generate.plugin.block.messages.invalid-theme-region'),
                    $theme_region
                )
            );

            return 1;
        }

        // @see use Drupal\Console\Command\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $build_services, $inputs);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);

        if ($theme_region) {
            // Load block to set theme region

            $block = $this->getEntityManager()->getStorage('block')->create(array('id'=> $plugin_id, 'plugin' => $plugin_id, 'theme' => $theme));
            $block->setRegion($theme_region);
            $block->save();
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $configFactory = $this->getConfigFactory();
        $theme = $configFactory->get('system.theme')->get('default');
        $theme_regions = system_region_list($theme, REGIONS_VISIBLE);
        
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
            $class_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.block.options.class-name'), 'DefaultBlock'),
                function ($class_name) {
                    return $this->validateClassName($class_name);
                },
                false,
                'DefaultBlock',
                null
            );
        }
        $input->setOption('class-name', $class_name);

        $default_label = $this->getStringHelper()->camelCaseToHuman($class_name);

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.block.options.label'), $default_label),
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
                $dialog->getQuestion($this->trans('commands.generate.plugin.block.options.plugin-id'), $machine_name),
                $machine_name
            );
        }
        $input->setOption('plugin-id', $plugin_id);

        // --theme-region option
        $theme_region = $input->getOption('theme-region');
        if (!$theme_region) {
            $theme_region =  $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.block.options.theme-region'), ''),
                function ($region) use ($theme_regions) {
                    return array_search($region, $theme_regions);
                },
                false,
                '',
                $theme_regions
            );
        }
        $input->setOption('theme-region', $theme_region);

        // --services option
        // @see Drupal\Console\Command\ServicesTrait::servicesQuestion
        $services_collection = $this->servicesQuestion($output, $dialog);
        $input->setOption('services', $services_collection);

        $output->writeln($this->trans('commands.generate.plugin.block.messages.inputs'));

        // @see Drupal\Console\Command\FormTrait::formQuestion
        $form = $this->formQuestion($output, $dialog);
        $input->setOption('inputs', $form);
    }

    protected function createGenerator()
    {
        return new PluginBlockGenerator();
    }
}
