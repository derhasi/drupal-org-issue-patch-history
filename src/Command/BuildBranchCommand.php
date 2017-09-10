<?php

namespace derhasi\drupalOrgIssuePatchHistory\Command;

use derhasi\drupalOrgIssuePatchHistory\Issue;
use derhasi\drupalOrgIssuePatchHistory\Repository;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use  \Symfony\Component\Console\Output\OutputInterface;

class BuildBranchCommand extends Command {

  protected $project;

  protected $issueID;

  protected $directory;

  protected $sourceBranch;

  protected $targetBranch;


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
      ->addArgument('sourceBranch', InputArgument::OPTIONAL, 'Branch name to apply patches on', '8.x-1.x')
      ->addArgument('targetBranch', InputArgument::OPTIONAL, 'Branch name to create for the given issue. Defaults to issue-[issueID].')
      ->addOption('reroll', NULL, InputOption::VALUE_NONE, 'Try to reapply the last succesful patch on the current source branch.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->project = $input->getArgument('project');
    $this->issueID = (int) $input->getArgument('issue');
    $this->directory = $input->getArgument('directory') ?: $this->project;
    $this->sourceBranch = $input->getArgument('sourceBranch');
    $this->targetBranch = $input->getArgument('targetBranch') ?: 'issue-' . $this->issueID;

    $output->writeln([
      'Project: ' . $this->project,
      'Issue: #' . $this->issueID,
      'Directory: ' . $this->directory,
      'Source branch: ' . $this->sourceBranch,
      'Target branch: ' . $this->targetBranch,
    ]);

    $issue = new Issue($this->issueID);

    $repo = new Repository($this->project, $this->directory, $this->sourceBranch);
    $repo->init();

    $targetBranchCreated = FALSE;

    // Creates a commit for each applicable patch.
    $lastSuccessfulComment = NULL;
    $lastSuccessfulPatchContent = '';
    foreach ($issue->getComments() as $comment) {
      // Skip comment, if no patch is available.
      if (!$comment->hasPatch()) {
        continue;
      }

      // Find the closest commit to the comments publication time.
      $hash = $repo->getHashByDateTime($comment->getPubDate());

      // Create issue branch, if it has not already been initialized.
      if (!$targetBranchCreated) {
        $repo->createBranchFromHash($this->targetBranch, $hash);
        $output->writeln(sprintf('Created issue branch %s', $this->targetBranch));
        $targetBranchCreated = TRUE;
      }

      // Fetch patch file.
      $patch_content = file_get_contents($comment->getPatch());

      // Create patch commit.
      $repo->checkout($hash);
      if ($repo->applyDiff($patch_content)) {
        $repo->commitAll($comment->getPatch(), '', $comment->getUser()->getGitAuthor());
        $patchHash = $repo->getCurrentHash();

        if ($this->applyDiffToRebase($repo, $hash, $patchHash, $this->targetBranch)) {
          $message = sprintf('[PATCH] Issue #%s (comment %s) by %s', $this->issueID, $comment->getId(), $comment->getUser()->getName());
          $body = 'Patch: '. $comment->getPatch() . PHP_EOL . PHP_EOL . $comment->getBody();
          $repo->commitAll($message, $body, $comment->getUser()->getGitAuthor());
          $output->writeln(sprintf("<info>%s - %s</info>", $message, $repo->getCurrentHash()));
        }
        else {
          // This should never happen, as the diff from current hash to desired
          // outcome was just created.
          $output->writeln(sprintf(
            '<error>Applying patch to %s from %s failed.</error>',
            $patchHash,
            $repo->getCurrentHash()
          ));
        }

        $lastSuccessfulComment = $comment;
        $lastSuccessfulPatchContent = $patch_content;
      }
      // Show warning in case we could not apply patch.
      else {
        $output->writeln(sprintf(
          '<comment>Could not apply patch of comment #%s by %s on %s: %s</comment>',
          $comment->getId(),
          $comment->getUser()->getName(),
          $hash,
          $comment->getPatch()
        ));
        continue;
      }
    }

    // Apply the last succesful patch to the latest commit of the source branch.
    if ($lastSuccessfulComment && $input->getOption('reroll')) {
      $repo->checkout($this->sourceBranch);
      // Do not update the source branch itself, but only the commit, so we can
      // pick the diff later.
      $sourceBranchHash = $repo->getCurrentHash();
      $repo->checkout($sourceBranchHash);
      if ($repo->applyDiff($lastSuccessfulPatchContent)) {
        $repo->commitAll('Latest patch reapplied', '', '');
        $patchHash = $repo->getCurrentHash();
        if ($this->applyDiffToRebase($repo, $this->sourceBranch, $patchHash, $this->targetBranch)) {

          $message = sprintf('[PATCH_REAPPLIED] Issue #%s (comment %s) by %s', $this->issueID, $lastSuccessfulComment->getId(), $lastSuccessfulComment->getUser()->getName());
          $repo->commitAll($message, '', '');
          $output->writeln(sprintf("<info>%s - %s</info>", $message, $repo->getCurrentHash()));
        }
      }
      else {
        $output->writeln(sprintf('<error>Could not apply last patch from comment %s to branch %s</error>', $lastSuccessfulComment->getId(), $this->sourceBranch));
      }
    }

  }

  /**
   * Applies the patched state, after target was rebased onto base.
   *
   * @param Repository $repo
   * @param $baseRev
   * @param $patchedRev
   * @param $targetRev
   *
   * @return TRUE if patch could be succesfully
   */
  protected function applyDiffToRebase(Repository $repo, $baseRev, $patchedRev, $targetRev) {

    // Update target branch.
    // Rebase the target branch on the base hash.
    $repo->checkout($targetRev);
    $repo->rebase($baseRev, ['strategy' => 'recursive', 'strategy-option' => 'ours']);

    // Calculate the diff to the patch itself from the rebased branch.
    $diff = $repo->diff($patchedRev, ['R' => true]);

    return $repo->applyDiff($diff);
  }
}
