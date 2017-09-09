<?php

namespace derhasi\drupalOrgIssuePatchHistory\Command;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use  \Symfony\Component\Console\Output\OutputInterface;

class BuildBranchCommand extends Command {

  protected $project;

  protected $issue;

  protected $directory;

  protected $branch;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('create-branch')
      ->setDescription('Creates a branch from a given issue\'s patch history.')
      ->setHelp('This command parses the issue history of a drupal.org issue and tries to build a representation of the patches within this history in a single branch.')
      ->addArgument('project', InputArgument::REQUIRED, 'The drupal org project machine name.')
      ->addArgument('issue', InputArgument::REQUIRED, 'The id of the issue to parse.')
      ->addArgument('directory', InputArgument::OPTIONAL, 'The directory of the repository to work with or to clone to. Defaults to project name.')
      ->addArgument('branch', InputArgument::OPTIONAL, 'Branch name for the repository to check on.', '8.x-1.x');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $this->project = $input->getArgument('project');
    $this->issue = $input->getArgument('issue');
    $this->directory = $input->getArgument('directory') ?: $this->project;
    $this->branch = $input->getArgument('branch');

    $output->writeln([
      $this->project,
      $this->issue,
      $this->directory,
      $this->branch,
    ]);
  }


}
