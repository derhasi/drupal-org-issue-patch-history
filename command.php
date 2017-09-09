<?php

require __DIR__ . '/vendor/autoload.php';

use \Wa72\HtmlPageDom\HtmlPageCrawler;

$issue_id = '2461695';
$project = 'paragraphs';
$branch = '8.x-1.x';

$issue_url = 'https://www.drupal.org/node/' . $issue_id;
$html = file_get_contents($issue_url);

$crawler = HtmlPageCrawler::create($html);

$comments = $crawler->filter('div.comment')->getIterator();

$patches = [];
foreach ($comments as $comment) {

  $cc = new HtmlPageCrawler($comment);
  $links = $cc->filter('.nodechanges-file-status-new .nodechanges-file-link a[href$=".patch"]');

  if ($links->count()) {
    $permalink = $cc->filter('.permalink')->first()->text();

    $match = [];
    if (!preg_match('/Comment #([0-9]+)/', $permalink, $match)) {
      break;
    }
    $comment_id = $match[1];

    $user = $cc->filter('a.username')->first();
    $time = $cc->filter('time[datetime]')->first()->attr('datetime');
    $body = $cc->filter('.field-name-comment-body .field-item')->text();

    $hrefs = $links->each(function($node) {
      return $node->attr('href');
    });

    $patches[$comment_id] = [
      'comment' => $comment_id,
      'user' => $user->text(),
      'uid' => $user->attr('data-uid'),
      'username' => substr(strstr($user->attr('href'), '/u/'), 3),
      'time' => $time,
      'files' => $hrefs,
      'body' => $body,
    ];
  }
}

ksort($patches);

// Read information from git repo.
$dir = __DIR__ . '/../temp';
$repo = "https://git.drupal.org/project/$project.git";
$branch_prefix = 'temp_' . time();

// Clone repo
$git_wrapper = new \GitWrapper\GitWrapper();
if (file_exists($dir)) {
  $git = $git_wrapper->workingCopy($dir);
}
else {
  $git = $git_wrapper->cloneRepository($repo, $dir, ['branch' => $branch]);
}

$target_branch = '';

foreach ($patches as $patch) {

  // LASTHASH=$(git rev-list -n 1 --before="$PROJECTTIME" $BRANCH)
  $patch_name = basename($patch['files'][0], '.patch');
  $comment_id = $patch['comment'];

  // Get commit hash of the time the patch was posted in the issue queue.
  $git->getOutput();
  $command = sprintf('rev-list -n 1 --before="%s" %s', $patch['time'], $branch);
  $hash = trim($git->run([$command])->getOutput());

  $git->checkout($hash);

  $message = sprintf('Issue %s [#%s] = User: %s - Time %s: %s => Hash: %s', $issue_id, $comment_id, $patch['user'], $patch['time'], $patch_name , $hash);
  echo $message . PHP_EOL;

  // Load patch file in temporary file.
  $patch_content = file_get_contents($patch['files'][0]);

  $success = git_apply_content($git, $patch_content);

  $this_branch = '';
  if ($success && $git->hasChanges()) {
    $git->add('', ['all' => true]);
    $git->commit([
      'm' => $message . PHP_EOL . PHP_EOL . $patch['body'],
      'author' => sprintf("%s <%s@%s.no-reply.drupal.org>", $patch['username'], $patch['username'], $patch['uid']),
    ]);

    // @todo: optionally use branch.
    $this_branch = "$branch_prefix/issue/$issue_id-comments/$comment_id";
    $git->checkoutNewBranch($this_branch);
  }
  else {
    echo "[WARNING] NO CHANGES or Could not apply patch" . PHP_EOL;
  }

  // Clear output buffer.
  $git->getOutput();

  // Update target branch.
  if (!empty($this_branch)) {
    if (empty($target_branch)) {
      $target_branch = "$branch_prefix/issue/$issue_id";
      $git->checkout($hash)->checkoutNewBranch($target_branch);
    }

    // Rebase the target branch on the base hash.
    $git->checkout($target_branch)
      ->rebase($hash, ['strategy' => 'recursive', 'strategy-option' => 'ours']);

    // Calculate the diff to the patch itself from the rebased branch.
    $git->getOutput();
    $diff = $git->diff($this_branch, ['R' => true])->getOutput();
    $success = git_apply_content($git, $diff);

    if ($success) {
      $git->add('', ['all' => true]);
      $git->commit([
        'm' => $message . PHP_EOL . PHP_EOL . $patch['body'],
        'author' => sprintf("%s <%s@%s.no-reply.drupal.org>", $patch['username'], $patch['username'], $patch['uid']),
      ]);
    }

    $git->getOutput();
  }
}

// Checkout main branch again.
$git->checkout($branch);


function git_apply_content(\GitWrapper\GitWorkingCopy $git, $diff, $p_max = 5) {

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
      $git->apply($tmpFilename, ['p' => $p])->getOutput();
      $success = TRUE;
    }
    catch (\Exception $e) {
      $p++;
    }
  } while(!$success && $p <= $p_max);

  // Clear output buffer.
  $git->getOutput();


  return $success;
}
