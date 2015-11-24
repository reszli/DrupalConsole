<?php

/**
 * @file
 * Contains Drupal\Console\Command\FormTrait.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait FormTrait
{
    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function formQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        if ($dialog->askConfirmation(
            $output,
            $dialog->getQuestion($this->trans('commands.common.questions.inputs.confirm'), 'yes', '?'),
            true
        )
        ) {
            $input_types = [
              'color',
              'checkbox',
              'checkboxes',
              'date',
              'datetime',
              'fieldset',
              'email',
              'number',
              'password',
              'password_confirm',
              'range',
              'radios',
              'select',
              'tel',
              'textarea',
              'textfield',
            ];

            $inputs = [];
            $fieldsets = [];
            while (true) {

                // Type input
                $input_type = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.type'), 'textfield', ':'),
                    function ($input) use ($input_types) {
                        if (!in_array($input, $input_types)) {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.common.questions.inputs.invalid'), $input)
                            );
                        }

                        return $input;
                    },
                    false,
                    'textfield',
                    $input_types
                );

                // Label for input
                $inputLabelMessage = $input_type == 'fieldset'?$this->trans('commands.common.questions.inputs.title'):$this->trans('commands.common.questions.inputs.label');
                $input_label = $dialog->ask(
                    $output,
                    $dialog->getQuestion('  '.$inputLabelMessage, '', ':'),
                    null
                );

                if (empty($input_label)) {
                    break;
                }

                // Machine name
                $input_machine_name = $this->getStringHelper()->createMachineName($input_label);

                $input_name = $dialog->ask(
                    $output,
                    $dialog->getQuestion(
                        '  '.$this->trans('commands.common.questions.inputs.machine_name'),
                        $input_machine_name,
                        ':'
                    ),
                    $input_machine_name
                );

                if ($input_type == 'fieldset') {
                    $fieldsets[$input_machine_name] = $input_label;
                }

                $input_fieldset = '';
                if ($input_type != 'fieldset' && !empty($fieldsets)) {
                    $input_fieldset = $dialog->askAndValidate(
                        $output,
                        $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.fieldset'), '', ':'),
                        function ($fieldset) {
                            return $fieldset;
                        },
                        false,
                        '',
                        $fieldsets
                    );

                    $input_fieldset = array_search($input_fieldset, $fieldsets);
                }

                $maxlength = null;
                $size = null;
                if (in_array($input_type, array('textfield', 'password', 'password_confirm'))) {
                    $maxlength = $dialog->ask(
                        $output,
                        $dialog->getQuestion('  Maximum amount of character', '', ':'),
                        null
                    );

                    $size = $dialog->ask(
                        $output,
                        $dialog->getQuestion('  Width of the textfield (in characters)', '', ':'),
                        null
                    );
                }

                if ($input_type == 'select') {
                    $size = $dialog->ask(
                        $output,
                        $dialog->getQuestion('  Size of multiselect box (in lines)', '', ':'),
                        null
                    );
                }

                $input_options = '';
                if (in_array($input_type, array('checkboxes', 'radios', 'select'))) {
                    $input_options = $dialog->ask(
                        $output,
                        $dialog->getQuestion('  Input options separated by comma', '', ':'),
                        null
                    );
                }

                // Prepare options as an array
                if (strlen(trim($input_options))) {
                    // remove spaces in options and empty options
                    $input_options = array_filter(array_map('trim', explode(',', $input_options)));
                    // Create array format for options
                    foreach ($input_options as $key => $value) {
                        $input_options_output[$key] = "\$this->t('".$value."') => \$this->t('".$value."')";
                    }

                    $input_options = 'array('.implode(', ', $input_options_output).')';
                }

                // Description for input
                $input_description = $dialog->ask(
                    $output,
                    $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.description'), '', ':'),
                    null
                );

                if ($input_type != 'fieldset') {
                    // Default value for input
                    $default_value = $dialog->ask(
                        $output,
                        $dialog->getQuestion('  ' . $this->trans('commands.common.questions.inputs.default-value'), '', ':'),
                        null
                    );
                }

                // Weight for input
                $weight = $dialog->ask(
                    $output,
                    $dialog->getQuestion('  '.$this->trans('commands.common.questions.inputs.weight'), '', ':'),
                    null
                );

                array_push(
                    $inputs, array(
                    'name' => $input_name,
                    'type' => $input_type,
                    'label' => $input_label,
                    'options' => $input_options,
                    'description' => $input_description,
                    'maxlength' => $maxlength,
                    'size' => $size,
                    'default_value' => $default_value,
                    'weight' => $weight,
                    'fieldset' => $input_fieldset,
                    )
                );
            }

            return $inputs;
        }

        return;
    }
}
