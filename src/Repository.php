<?php
/**
 * @file
 * Repository.php for paragraphs
 */

namespace derhasi\drupalOrgIssuePatchHistory;


class Repository {

  protected $project;

  protected $branch;

  protected $directory;

  /**
   * @var \GitWrapper\GitWorkingCopy
   */
  protected $git;

  public function __construct($project, $directory, $branch) {
    $this->project = $project;
    $this->branch = $branch;
    $this->directory = $directory;
  }

  protected function getRepositoryURL() {
    return "https://git.drupal.org/project/{$this->project}.git";
  }

  /**
   * Initializes repository in directory.
   */
  public function init() {
    $git_wrapper = new \GitWrapper\GitWrapper();
    if (file_exists($this->directory)) {
      $this->git = $git_wrapper->workingCopy($this->directory);
      $this->git->checkout($this->branch);
    }
    else {
      $this->git = $git_wrapper->cloneRepository($this->getRepositoryURL(), $this->directory, ['branch' => $this->branch]);
    }
  }

  public function getHashByDateTime($datetime) {
    $this->git->getOutput();
    $command = sprintf('rev-list -n 1 --before="%s" %s', $datetime, $this->branch);
    $hash_output = $this->git->run([$command])->getOutput();
    return trim($hash_output);
  }

  public function createBranchFromHash($branch, $hash) {
    return $this->git->checkout($hash)
      ->checkoutNewBranch($branch)
      ->getOutput();
  }

}
