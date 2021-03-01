<?php
/**
 * Class LogViewerTest
 *
 * @package WPDiscourse
 */

use \WPDiscourse\Logs\FileManager;
use \WPDiscourse\Logs\FileHandler;
use \WPDiscourse\Logs\Logger;
use \WPDiscourse\Admin\LogViewer;

/**
 * Logger test case.
 */
class LogViewerTest extends WP_UnitTestCase {

    /**
     * It should be disabled if file handler is disabled
     */
    public function test_enabled() {
        $handler        = new FileHandler( new FileManager() );
        $handler_double = \Mockery::mock( $handler )->makePartial();
        $handler_double->shouldReceive( 'enabled' )->andReturn( false );

        $viewer = new LogViewer();
        $viewer->setup_log_viewer( $handler_double );

        $this->assertFalse( $viewer->is_enabled() );

        ob_start();
        $viewer->log_viewer_markup();
        $markup = ob_get_contents();
        ob_end_clean();

        $this->assertXmlStringEqualsXmlString( $markup, '<div class="inline"><p>Logs are disabled.</p></div>' );
    }

  	/**
  	 * It should retrieve logs and map them to date, number and file
  	 */
  	public function test_log_retrieval() {
        $handler = new FileHandler( new FileManager() );
        $logger  = Logger::create( 'test', $handler );
        $logger->info( 'New Log' );

        $viewer = new LogViewer();
        $viewer->setup_log_viewer();

        $date   = $handler->getDate();
        $number = $handler->currentFileNumber();
        $file   = $handler->currentFileUrl();

        $this->assertArraySubset(
            array(
                "$date-$number" => array(
                    'date'   => $date,
                    'number' => $number,
                    'file'   => $file,
                ),
            ),
            $viewer->get_logs()
        );
    }
}
