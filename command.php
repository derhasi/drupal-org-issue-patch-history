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

    $user = $cc->filter('a.username')->first()->text();
    $time = $cc->filter('time[datetime]')->first()->attr('datetime');

    $hrefs = $links->each(function($node) {
      return $node->attr('href');
    });

    $patches[$comment_id] = [
      'comment' => $comment_id,
      'user' => $user,
      'time' => $time,
      'files' => $hrefs,
    ];
  }
}

ksort($patches);

// Read information from git repo.
$dir = __DIR__ . '/../temp';
$repo = "https://git.drupal.org/project/$project.git";
$branch_prefix = time();

// Clone repo
$git_wrapper = new \GitWrapper\GitWrapper();
if (file_exists($dir)) {
  $git = $git_wrapper->workingCopy($dir);
}
else {
  $git = $git_wrapper->cloneRepository($repo, $dir, ['branch' => $branch]);
}

foreach ($patches as $patch) {

  // LASTHASH=$(git rev-list -n 1 --before="$PROJECTTIME" $BRANCH)
  $patch_name = basename($patch['files'][0], '.patch');
  $comment_id = $patch['comment'];

  // Get commit hash of the time the patch was posted in the issue queue.
  $git->getOutput();
  $command = sprintf('rev-list -n 1 --before="%s" %s', $patch['time'], $branch);
  $hash = trim($git->run([$command])->getOutput());

  $git->checkout($hash);
  $git->checkoutNewBranch("$branch_prefix/issue/$issue_id/$comment_id");

  $message = sprintf('Issue %s [#%s] = User: %s - Time %s: %s => Hash: %s', $issue_id, $comment_id, $patch['user'], $patch['time'], $patch_name , $hash);
  echo $message . PHP_EOL;

  // Load patch file in temporary file.
  $patch_content = file_get_contents($patch['files'][0]);

  $tmpHandle = tmpfile();
  fwrite($tmpHandle, $patch_content);
  // Get tmp file location.
  $metaDatas = stream_get_meta_data($tmpHandle);
  $tmpFilename = $metaDatas['uri'];

  $p = 0;
  $retry = TRUE;
  do {

    try {
      $ret = $git->apply($tmpFilename, ['p' => $p])->getOutput();
      $retry = FALSE;
    }
    catch (\Exception $e) {
      $p++;
    }
  } while($retry && $p < 5);

  if ($git->hasChanges()) {
    $git->add('', ['all' => true]);
    $git->commit($message);
  }
  else {
    echo "NO CHANGES or Could not apply patch";
  }

  echo $git->getOutput();

  fclose($tmpHandle);

}
