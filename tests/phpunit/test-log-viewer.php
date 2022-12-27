<?php
/**
 * Class LogViewerTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\Logs\FileManager;
use \WPDiscourse\Logs\FileHandler;
use \WPDiscourse\Logs\Logger;
use \WPDiscourse\Admin\LogViewer;
use \WPDiscourse\Admin\FormHelper;
use \WPDiscourse\Test\UnitTest;

/**
 * Logger test case.
 */
class LogViewerTest extends UnitTest {

    /**
     * Instance of LogViewer.
     *
     * @access protected
     * @var \WPDiscourse\LogViewer\LogViewer
     */
    protected $viewer;

    /**
     * Setup each test.
     */
    public function setUp() {
        parent::setUp();

        $this->viewer = new LogViewer( FormHelper::get_instance() );
        $this->viewer->setup_options( self::$plugin_options );
    }

    /**
     * Teardown each test.
     */
    public function tearDown() {
      parent::tearDown();

      self::$plugin_options['logs-enabled'] = 1;
      $this->viewer->setup_options( self::$plugin_options );
    }

    /**
     * It should be disabled if file handler is disabled
     */
    public function test_file_handler_not_enabled() {
        $handler        = new FileHandler( new FileManager() );
        $handler_double = \Mockery::mock( $handler )->makePartial();
        $handler_double->shouldReceive( 'enabled' )->andReturn( false );

        $this->viewer->setup_log_viewer( $handler_double );

        $this->assertFalse( $this->viewer->is_enabled() );

        ob_start();
        $this->viewer->log_viewer_markup();
        $markup = ob_get_contents();
        ob_end_clean();

        $this->assertXmlStringEqualsXmlString( $markup, '<div class="inline"><p>Logs are disabled.</p></div>' );
    }

    /**
     * It should be disabled if logs are not enabled
     */
    public function test_logs_not_enabled() {
        self::$plugin_options['logs-enabled'] = 0;
        $this->viewer->setup_options( self::$plugin_options );
        $this->viewer->setup_log_viewer();

        $this->assertFalse( $this->viewer->is_enabled() );

        ob_start();
        $this->viewer->log_viewer_markup();
        $markup = ob_get_contents();
        ob_end_clean();

        $this->assertXmlStringEqualsXmlString( $markup, '<div class="inline"><p>Logs are disabled.</p></div>' );
    }

  	/**
  	 * It should retrieve logs and map them to date, number and file
  	 */
  	public function test_log_retrieval() {
        $handler = new FileHandler( new FileManager() );
        $logger  = Logger::create( 'test', self::$plugin_options, $handler );
        $logger->info( 'New Log' );

        $this->viewer->setup_log_viewer();

        $date   = $handler->get_date();
        $number = $handler->current_file_number();
        $file   = $handler->current_file_url();

        $this->assertArraySubset(
            array(
                "$date-$number" => array(
                    'date'   => $date,
                    'number' => $number,
                    'file'   => $file,
                ),
            ),
            $this->viewer->get_logs()
        );
    }
}
