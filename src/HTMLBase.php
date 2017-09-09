<?php

namespace derhasi\drupalOrgIssuePatchHistory;

use Wa72\HtmlPageDom\HtmlPageCrawler;

abstract class HTMLBase {

  /**
   * @var mixed
   */
  protected $data;

  /**
   * @var \Wa72\HtmlPageDom\HtmlPageCrawler
   */
  protected $crawler;

  /**
   * Sets the data the crawler shall work with.
   *
   * @param $data
   *
   * @return mixed
   */
  protected function setData($data) {
    return $this->data;
  }

  /**
   * Provides the crawler instance for this data.
   *
   * @return \Wa72\HtmlPageDom\HtmlPageCrawler
   */
  protected function getCrawler() {
    if (!isset($this->crawler)) {
      $this->crawler = new HtmlPageCrawler($this->data);
    }
    return $this->crawler;
  }
}
