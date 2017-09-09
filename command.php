<?php

require __DIR__ . '/vendor/autoload.php';

use \Wa72\HtmlPageDom\HtmlPageCrawler;

$issue_id = '2461695';
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
