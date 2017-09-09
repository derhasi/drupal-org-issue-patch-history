<?php

namespace derhasi\drupalOrgIssuePatchHistory\Command;

use derhasi\drupalOrgIssuePatchHistory\Issue;
use derhasi\drupalOrgIssuePatchHistory\Repository;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use  \Symfony\Component\Console\Output\OutputInterface;

class BuildBranchCommand extends Command {

  protected $project;

  protected $issueID;

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
    $this->issueID = $input->getArgument('issue');
    $this->directory = $input->getArgument('directory') ?: $this->project;
    $this->branch = $input->getArgument('branch');

    $output->writeln([
      $this->project,
      $this->issueID,
      $this->directory,
      $this->branch,
    ]);

    $issue = new Issue($this->issueID);

    $repo = new Repository($this->project, $this->directory, $this->branch);
    $repo->init();

    $issue_branch = '';

    foreach ($issue->getComments() as $comment) {
      // Skip comment, if no patch is available.
      if (!$comment->hasPatch()) {
        continue;
      }

      $hash = $repo->getHashByDateTime($comment->getPubDate());

      // Create issue branch, if it has not already been initialized.
      if (empty($issue_branch)) {
        $issue_branch = "issue-{$this->issueID}";
        $repo->createBranchFromHash($issue_branch, $hash);
        $output->writeln(sprintf('Created issue branch %s', $issue_branch));
      }

      //...

    }
  }


}
