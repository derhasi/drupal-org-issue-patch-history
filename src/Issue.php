<?php

namespace derhasi\drupalOrgIssuePatchHistory;

class Issue extends HTMLBase {

  protected $id;

  protected $url;

  public function __construct($id) {
    $this->id = (int) $id;
    $this->url = 'https://www.drupal.org/node/' . $this->id;
  }

  /**
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @return \derhasi\drupalOrgIssuePatchHistory\Comment[]
   */
  public function getComments() {
    $comments = $this->getCrawler()
      ->filter('div.comment')
      ->getIterator();

    $return = [];
    foreach ($comments as $comment) {
      $return[] = new Comment($comment);
    }

    return $return;
  }

  /**
   * Loads html form the issue page.
   *
   * @return string
   */
  protected function loadHtml() {
    if (!isset($this->data)) {
      $this->data = file_get_contents($this->url);
    }
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCrawler() {
    $this->loadHtml();
    return parent::getCrawler();
  }

}
