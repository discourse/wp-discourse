<?php
/**
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Logs;

/**
 * Class LineFormatter
 */
class LineFormatter extends \Monolog\Formatter\LineFormatter {
  
  /**
  * LineFormatter constructor
  */
  public function __construct() {
    $format = null;
    $dateFormat = null;
    $allowInlineLineBreaks = false;
    $ignoreEmptyContextAndExtra = true;
    parent::__construct( $format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra );
  }
}