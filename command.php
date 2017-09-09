<?php

require __DIR__ . '/vendor/autoload.php';

use \Wa72\HtmlPageDom\HtmlPageCrawler;

$issue_url = 'https://www.drupal.org/node/2461695';
$html = file_get_contents($issue_url);

$crawler = HtmlPageCrawler::create($html);

$comments = $crawler->filter('div.comment')->getIterator();

$patches = [];
foreach ($comments as $comment) {

  $cc = new HtmlPageCrawler($comment);
  $user = $cc->filter('a.username')->first()->text();
  $time = $cc->filter('time[datetime]')->first()->attr('datetime');
  $links = $cc->filter('.nodechanges-file-status-new .nodechanges-file-link a[href$=".patch"]');

  if ($links->count()) {

    $hrefs = $links->each(function($node) {
      return $node->attr('href');
    });

    $patches[] = [
      'user' => $user,
      'time' => $time,
      'files' => $hrefs,
    ];

  }
}

