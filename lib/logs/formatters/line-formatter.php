<?php
/**
 * Formats log lines.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Logs;

/**
 * Class LineFormatter
 */
class LineFormatter extends \WPDiscourse\Monolog\Formatter\LineFormatter {

    /**
     * LineFormatter constructor
     */
    public function __construct() {
    		$format                         = null;
    		$date_format                    = null;
    		$allow_inline_line_breaks       = false;
    		$ignore_empty_context_and_extra = true;
    		parent::__construct( $format, $date_format, $allow_inline_line_breaks, $ignore_empty_context_and_extra );
    }
}
