<?php
/**
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use \Monolog\Formatter\LineFormatter;

/**
 * Class WPDCLogFileFormatter
 */
class WPDCLogFileFormatter extends LineFormatter {
  
  /**
  * WPDCLogFileFormatter constructor
  */
  public function __construct() {
    $format = null;
    $dateFormat = null;
    $allowInlineLineBreaks = false;
    $ignoreEmptyContextAndExtra = true;
    parent::__construct( $format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra );
  }
}