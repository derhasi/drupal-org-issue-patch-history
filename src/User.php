<?php

namespace derhasi\drupalOrgIssuePatchHistory;

class User extends HTMLBase {

  const ALIAS_PREFIX = '/u/';

  protected $id;

  protected $name;

  protected $alias;

  public function __construct($data) {
    $this->setData($data);
  }

  /**
   * @return mixed
   */
  public function getId() {
    if (!isset($this->id)) {
      $this->id = $this->getCrawler()->attr('data-uid');
    }
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getName() {
    if (!isset($this->name)) {
      $this->id = $this->getCrawler()->text();
    }
    return $this->name;
  }

  /**
   * @return mixed
   */
  public function getAlias() {
    if (!isset($this->alias)) {
      $href = $this->getCrawler()->attr('href');
      $this->alias = substr(strstr($href, static::ALIAS_PREFIX), strlen(static::ALIAS_PREFIX));
    }
    return $this->alias;
  }
}
