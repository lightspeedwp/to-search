<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package tour-operator
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * Assets are built by @wordpress/scripts into build/:
 *
 * 1. style-index.css - frontend + backend.
 * 2. index.js        - editor only.
 * 3. index.css       - editor only.
 *
 * Script dependencies and a content-hash version come from the generated
 * build/index.asset.php rather than being hardcoded, so the dependency list
 * stays correct as the block's imports change and the cache bust follows the
 * built file. The previous registration passed null as the version, which
 * left WordPress with nothing to bust on.
 *
 * @since 1.0.0
 */
function to_search_block_assets() {
	$build_dir  = plugin_dir_path( dirname( __FILE__ ) ) . 'build/';
	$build_url  = plugins_url( 'build/', dirname( __FILE__ ) );
	$asset_file = $build_dir . 'index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	// Register block styles for both frontend + backend.
	wp_register_style(
		'to-search-block-style',
		$build_url . 'style-index.css',
		array(),
		$asset['version']
	);

	// Register block editor script for backend.
	wp_register_script(
		'to-search-block-editor',
		$build_url . 'index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// Register block editor styles for backend.
	wp_register_style(
		'to-search-block-editor-style',
		$build_url . 'index.css',
		array( 'wp-edit-blocks' ),
		$asset['version']
	);

	/**
	 * Register Gutenberg block on server-side.
	 *
	 * Register the block on server-side to ensure that the block
	 * scripts and styles for both frontend and backend are
	 * enqueued when the editor loads.
	 *
	 * @since 1.16.0
	 */
	register_block_type(
		'to-search/to-search-block',
		array(
			'style'         => 'to-search-block-style',
			'editor_script' => 'to-search-block-editor',
			'editor_style'  => 'to-search-block-editor-style',
		)
	);
}

// Hook: Block assets.
add_action( 'init', 'to_search_block_assets' );
