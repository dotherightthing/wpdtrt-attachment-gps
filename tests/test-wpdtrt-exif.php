<?php
/**
 * Unit tests, using PHPUnit, wp-cli, WP_UnitTestCase
 *
 * The plugin is 'active' within a WP test environment
 * 	so the plugin class has already been instantiated
 * 	with the options set in wpdtrt-gallery.php
 *
 * Only function names prepended with test_ are run.
 * $debug logs are output with the test output in Terminal
 * A failed assertion may obscure other failed assertions in the same test.
 *
 * @package     WPDTRT_Exif
 * @version     0.0.1
 * @since       0.7.5
 *
 * @see http://kb.dotherightthing.dan/php/wordpress/php-unit-testing-revisited/ - Links
 * @see http://richardsweeney.com/testing-integrations/
 * @see https://gist.github.com/benlk/d1ac0240ec7c44abd393 - Collection of notes on WP_UnitTestCase
 * @see https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes/factory.php
 * @see https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes//factory/
 * @see https://stackoverflow.com/questions/35442512/how-to-use-wp-unittestcase-go-to-to-simulate-current-pageclass-wp-unittest-factory-for-term.php
 * @see https://codesymphony.co/writing-wordpress-plugin-unit-tests/#object-factories
 */

/**
 * WP_UnitTestCase unit tests for wpdtrt_exif
 */
class wpdtrt_exifTest extends WP_UnitTestCase {

    /**
     * Compare two HTML fragments.
     *
     * @param string $expected Expected value
     * @param string $actual Actual value
     * @param string $error_message Message to show when strings don't match
     *
     * @uses https://stackoverflow.com/a/26727310/6850747
     */
    protected function assertEqualHtml($expected, $actual, $error_message) {
        $from = ['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/> </s'];
        $to   = ['>',            '<',            '\\1',      '><'];
        $this->assertEquals(
            preg_replace($from, $to, $expected),
            preg_replace($from, $to, $actual),
            $error_message
        );
    }

    /**
     * SetUp
     * Automatically called by PHPUnit before each test method is run
     */
    public function setUp() {
  		// Make the factory objects available.
        parent::setUp();

	    $this->post_id_1 = $this->create_post( array(
	    	'post_title' => 'DTRT EXIF test',
	    	'post_content' => 'This is a simple test'
	    ) );

        // Attachment (for testing custom sizes and meta)
        $this->attachment_id_1 = $this->create_attachment( array(
            'filename' => 'images/test1.jpg',
            'parent_post_id' => $this->post_id_1
        ) );
    }

    /**
     * TearDown
     * Automatically called by PHPUnit after each test method is run
     *
     * @see https://codesymphony.co/writing-wordpress-plugin-unit-tests/#object-factories     
     */
    public function tearDown() {

    	parent::tearDown();

        wp_delete_post( $this->post_id_1, true );
        wp_delete_post( $this->attachment_id_1, true );

        $this->delete_sized_images();
    }

    /**
     * Create post
     *
     * @param string $post_title Post title
     * @param string $post_date Post date
     * @param array $term_ids Taxonomy term IDs
     * @return number $post_id
     *
     * @see https://developer.wordpress.org/reference/functions/wp_insert_post/
     * @see https://wordpress.stackexchange.com/questions/37163/proper-formatting-of-post-date-for-wp-insert-post
     * @see https://codex.wordpress.org/Function_Reference/wp_update_post
     */
    public function create_post( $options ) {

        $post_title = null;
        $post_date = null;
        $post_content = null;

        extract( $options, EXTR_IF_EXISTS );

        $post_id = $this->factory->post->create([
           'post_title' => $post_title,
           'post_date' => $post_date,
           'post_content' => $post_content,
           'post_type' => 'post',
           'post_status' => 'publish'
        ]);

        return $post_id;
    }

    /**
     * Create attachment, upload media file, generate sizes
     * @see http://develop.svn.wordpress.org/trunk/tests/phpunit/includes/factory/class-wp-unittest-factory-for-attachment.php
     * @see https://core.trac.wordpress.org/ticket/42990 - Awaiting Review
     * @todo Factory method not available - see create_attachment(), below
     */
    public function create_attachment_simple( $options ) {

        $filename = null;
        $parent_post_id = null;

        extract( $options, EXTR_IF_EXISTS );

        $attachment_id = $this->factory->attachment->create_upload_object([
            'file' => $filename,
            'parent' => $parent_post_id
        ]);
    }

    /**
     * Create attachment and attach it to a post
     *
     * @param string $filename Filename
     * @param number $parent_post_id The ID of the post this attachment is for
     * @return number $attachment_id
     *
     * @see https://developer.wordpress.org/reference/functions/wp_insert_attachment/
     * @see http://develop.svn.wordpress.org/trunk/tests/phpunit/includes/factory/class-wp-unittest-factory-for-attachment.php
     */
    public function create_attachment( $options ) {

        $filename = null;
        $parent_post_id = null;

        extract( $options, EXTR_IF_EXISTS );

        // Check the type of file. We'll use this as the 'post_mime_type'
        $filetype = wp_check_filetype( basename( $filename ), null );

        // Get the path to the upload directory
        $wp_upload_dir = wp_upload_dir();

        // Create the attachment from an array of post data
        $attachment_id = $this->factory->attachment->create([
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
            'post_mime_type' => $filetype['type'],
            //'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
            //'post_content'   => '',
            //'post_status'    => 'inherit',
            'post_parent'    => $parent_post_id, // test factory only
            'file'           => $filename // test factory only
        ]);

        // generate image sizes
        // @see https://wordpress.stackexchange.com/a/134252
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        return $attachment_id;
    }

    // ########## TEST ########## //

    /**
     * Test that the custom field keys and values are output as ?
     *
     * @see test-wpdtrt-gallery.php
     */
    public function __test_attachment_fields() {
        return true; // TODO
    }

    /**
     * Test the two-way conversion from DMS to DD
     *
     * @see https://github.com/dotherightthing/wpdtrt-exif/issues/2
     */
    public function test_helper_convert_dms_to_dd() {

        $attachment_metadata = $this->plugin->get_attachment_metadata( $this->attachment_id_1 );

        // Latitude in Degrees Minutes Seconds fractions
        $latitude = $attachment_metadata['image_meta']['latitude'];

        $latitude_dd = $this->plugin->helper_convert_dms_to_dd( $latitude );
        $latitude_dms = $this->plugin->helper_convert_dd_to_dms( $latitude_dd );

        $this->assertEquals(
            '39.9958333333',
            $latitude_dd,
            'Incorrect conversion from DMS to DD'
        );

        $this->assertEquals(
            Array (
                '39/1',
                '56/1',
                '375/100',
            ),
            $latitude_dms,
            'Incorrect conversion from DD to DMS'
        );
    }
}
