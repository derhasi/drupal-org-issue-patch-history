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

  public function getCurrentHash() {
    $this->git->getOutput();
    $hash_output = $this->git->run(['rev-parse HEAD'])->getOutput();
    return trim($hash_output);
  }

  public function checkout($rev) {
    $this->git->checkout($rev);
  }

  public function createBranchFromHash($branch, $hash) {
    return $this->git->checkout($hash)
      ->checkoutNewBranch($branch)
      ->getOutput();
  }

  public function rebase($hash, $options) {
    $this->git->rebase($hash, $options);
  }

  public function diff($hash, $options) {
    $this->git->getOutput();
    $this->git->diff($hash, $options);
    return $this->git->getOutput();
  }

  public function applyDiff($diff, $p_max = 5) {
    // Create a temporary file.
    $tmpHandle = tmpfile();
    fwrite($tmpHandle, $diff);

    // Get tmp file location.
    $metaDatas = stream_get_meta_data($tmpHandle);
    $tmpFilename = $metaDatas['uri'];

    $p = 0;
    $success = FALSE;
    do {

      try {
        $this->git->apply($tmpFilename, ['p' => $p])->getOutput();
        $success = TRUE;
      }
      catch (\Exception $e) {
        $p++;
      }
    } while(!$success && $p <= $p_max);

    // Clear output buffer.
    $this->git->getOutput();

    return $success;
  }

  public function commitAll($subject, $body, $author) {
    $this->git->add('', ['all' => true]);
    $this->git->commit([
      'm' => $subject . PHP_EOL . PHP_EOL . $body,
      'author' => $author,
    ]);
  }

  public function getOutput() {
    return $this->git->getOutput();
  }

  public function clearOutputBuffer() {
    $this->git->getOutput();
  }
}
