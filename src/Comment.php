<?php
/**
 * @file
 * Comment.php for paragraphs
 */

namespace derhasi\drupalOrgIssuePatchHistory;

/**
 * Represents a drupal.org issue comment.
 */
class Comment extends HTMLBase {

  protected $patch;

  protected $user;

  protected $id;

  protected $permalink;

  protected $pubdate;

  protected $body;

  public function __construct($data) {
    $this->setData($data);
  }

  /**
   * Get URL of the patch file.
   *
   * @return string
   */
  public function getPatch() {
    if (!isset($this->patch)) {
      $links = $this->getCrawler()
        ->filter('.nodechanges-file-status-new .nodechanges-file-link a[href$=".patch"]');

      if ($links->count()) {
        $this->patch = $links->first()->attr('href');
      }
      else {
        $this->patch = '';
      }
    }

    return $this->patch;
  }

  /**
   * Checks if the comment has a patch attached.
   *
   * @return bool
   */
  public function hasPatch() {
    return !empty($this->getPatch());
  }

  /**
   * @return \derhasi\drupalOrgIssuePatchHistory\User
   */
  public function getUser() {
    if (!isset($this->user)) {
      $user = $this->getCrawler()
        ->filter('a.username')
        ->first();
      $this->user = new User($user->html());
    }
    return $this->user;
  }

  /**
   * @return int
   */
  public function getId() {
    if (!isset($this->id)) {
      $permalink = $this->getCrawler()
        ->filter('.permalink')
        ->first()
        ->text();

      $match = [];
      if (!preg_match('/Comment #([0-9]+)/', $permalink, $match)) {
        throw new \Exception('Comment ID not found.');
      }
      $this->id = $match[1];
    }

    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getPermalink() {
    if (!isset($this->permalink)) {
      $this->permalink = $this->getCrawler()
        ->filter('.permalink')
        ->first()
        ->attr('href');
    }

    return $this->permalink;
  }

  /**
   * @return string
   */
  public function getPubDate() {
    if (!isset($this->pubdate)) {
      $this->pubdate = $this->getCrawler()
        ->filter('time[datetime]')
        ->first()
        ->attr('datetime');
    }
    return $this->pubdate;
  }

  /**
   * @return string
   */
  public function getBody() {
    if (!isset($this->body)) {
      $this->body = $this->getCrawler()
        ->filter('.field-name-comment-body .field-item')
        ->text();
    }
    return (string) $this->body;
  }


}
