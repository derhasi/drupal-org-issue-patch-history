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

  protected $git;

  public function __construct($project, $branch, $directory) {
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

}
