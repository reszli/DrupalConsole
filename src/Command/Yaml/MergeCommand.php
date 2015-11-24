<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Yaml\MergeCommand.
 */

namespace Drupal\Console\Command\Yaml;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Command\Command;

class MergeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('yaml:merge')
            ->setDescription($this->trans('commands.yaml.merge.description'))
            ->addArgument(
                'yaml-destination',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.merge.arguments.yaml-destination')
            )
            ->addArgument(
                'yaml-files',
                InputArgument::IS_ARRAY,
                $this->trans('commands.yaml.merge.arguments.yaml-files')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yaml = new Parser();
        $dumper = new Dumper();
        $messageHelper = $this->getMessageHelper();

        $final_yaml = array();
        $yaml_destination = $input->getArgument('yaml-destination');
        $yaml_files = $input->getArgument('yaml-files');

        if (count($yaml_files) < 2) {
            $messageHelper->addErrorMessage($this->trans('commands.yaml.merge.messages.two-files-required'));

            return;
        }

        foreach ($yaml_files as $yaml_file) {
            try {
                $yaml_parsed = $yaml->parse(file_get_contents($yaml_file));
            } catch (\Exception $e) {
                $output->writeln('[+] <error>'.$this->trans('commands.yaml.merge.messages.error-parsing').': '.$e->getMessage().'</error>');

                return;
            }

            if (empty($yaml_parsed)) {
                $output->writeln(
                    '[+] <info>'.sprintf(
                        $this->trans('commands.yaml.merge.messages.wrong-parse'),
                        $yaml_file
                    ).'</info>'
                );
            }

            // Merge arrays
            $final_yaml = array_replace_recursive($final_yaml, $yaml_parsed);
        }

        try {
            $yaml = $dumper->dump($final_yaml, 10);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.yaml.merge.messages.error-generating').': '.$e->getMessage().'</error>');

            return;
        }

        try {
            file_put_contents($yaml_destination, $yaml);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.yaml.merge.messages.error-writing').': '.$e->getMessage().'</error>');

            return;
        }

        $output->writeln(
            '[+] <info>'.sprintf(
                $this->trans('commands.yaml.merge.messages.merged'),
                $yaml_destination
            ).'</info>'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator_filename = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception(' You must provide a valid file path.');
            }

            return $value;
        };

        $dialog = $this->getDialogHelper();

        // --yaml-destination option
        $yaml_destination = $input->getArgument('yaml-destination');
        if (!$yaml_destination) {
            $yaml_destination = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.yaml.merge.questions.yaml-destination'), ''),
                $validator_filename,
                false,
                null
            );
        }
        $input->setArgument('yaml-destination', $yaml_destination);

        $yaml_files = $input->getArgument('yaml-files');
        if (!$yaml_files) {
            $yaml_files = array();

            while (true) {
                $yaml_file = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion((count($yaml_files) < 2 ? $this->trans('commands.yaml.merge.questions.file') : $this->trans('commands.yaml.merge.questions.other-file')), ''),
                    function ($file) use ($yaml_files) {
                        if (count($yaml_files) < 2 && empty($file)) {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.yaml.merge.questions.invalid-file'), $file)
                            );
                        } elseif (in_array($file, $yaml_files)) {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.yaml.merge.questions.file-already-added'), $file)
                            );
                        } else {
                            return $file;
                        }
                    },
                    false,
                    null,
                    null
                );

                if (empty($yaml_file)) {
                    break;
                }

                $yaml_files[] = $yaml_file;
            }

            $input->setArgument('yaml-files', $yaml_files);
        }
    }
}
