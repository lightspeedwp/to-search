/**
 * BLOCK: my-block
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
import './styles/style.scss';
import './styles/editor.scss';

// These are imports rather than reads off the `wp` globals so that
// @wordpress/scripts' dependency extraction can see them and emit a correct
// dependency array into build/index.asset.php. Reading the globals produced an
// asset file listing only react-jsx-runtime, which would let this script load
// before wp-blocks and wp-i18n existed.
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
// InspectorControls moved here from wp.editor, deprecated since WordPress 5.3.
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
} from '@wordpress/components';

/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */

const blockAttributes = {
	columns: {
		type: 'number',
		default: 3,
	},
	placeholderText: {
		type: 'string',
		default: 'Where do you want to stay?',
	},
	searchButtonText: {
		type: 'string',
		default: 'Find',
	},
	postType: {
		type: 'string',
		default: 'default',
	},
	displayFacets: {
		type: 'string',
		default: '',
	},
	displayFacetsCombo: {
		type: 'string',
		default: 'false',
	},
};

registerBlockType( 'to-search/to-search-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'TO Search Block' ), // Block title.
	icon: 'search', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'TO Search Block' ),
		__( 'Tour Operator' ),
		__( 'search' ),
	],
	attributes: blockAttributes,


	edit( { attributes, className, setAttributes } ) {
		const { placeholderText, postType, displayFacets, searchButtonText, displayFacetsCombo } = attributes;

		function onChangePlaceholderText( updatedPlaceholderText ) {
			setAttributes( { placeholderText: updatedPlaceholderText } );
		}

		function onChangeSearchButtonText( updatedSearchButtonText ) {
			setAttributes( { searchButtonText: updatedSearchButtonText } );
		}

		function onChangeDisplayFacets( updatedDisplayFacets ) {
			setAttributes( { displayFacets: updatedDisplayFacets } );
		}

		// Post Type options
		const postTypeOptions = [
			{ value: 'default', label: __( 'Global' ) },
			{ value: 'tour', label: __( 'Tours' ) },
			{ value: 'accommodation', label: __( 'Accommodations' ) },
			{ value: 'destination', label: __( 'Destinations' ) },
			// { value: 'review', label: __( 'Reviews' ) },
			// { value: 'special', label: __( 'Specials' ) },
		];

		// Orderby options
		const displayFacetsComboOptions = [
			{ value: 'true', label: __( 'Yes' ) },
			{ value: 'false', label: __( 'No' ) },
		];

		let comboBox;

		return (
			<div>
				{
					<InspectorControls key="inspector">
						<PanelBody title={ __( 'Shortcode Settings' ) } >
							<TextControl
								label={ __( 'Placeholder' ) }
								type="text"
								value={ placeholderText }
								onChange={ onChangePlaceholderText }
							/>
							<TextControl
								label={ __( 'Search Button Text' ) }
								type="text"
								value={ searchButtonText }
								onChange={ onChangeSearchButtonText }
							/>
							<SelectControl
								label={ __( 'Type of Content' ) }
								description={ __( 'Choose the parameter you wish your content to be' ) }
								options={ postTypeOptions }
								value={ postType }
								onChange={ ( value ) => setAttributes( { postType: value } ) }
							/>
							<TextControl
								label={ __( 'Facets to display, like: "accommodation_type | destination_to_accommodation" ' ) }
								value={ displayFacets }
								onChange={ onChangeDisplayFacets }
							/>
							{ displayFacets && !! displayFacets.length && (
								<SelectControl
									label={ __( 'Facet Selector Combo Box' ) }
									description={ __( 'Choose if the facets will show in a combo selector' ) }
									options={ displayFacetsComboOptions }
									value={ displayFacetsCombo }
									onChange={ ( value ) => setAttributes( { displayFacetsCombo: value } ) }
								/>
							) }
						</PanelBody>
					</InspectorControls>
				}

				<div id="search-block">
					[lsx_search_form engine=&quot;{ postType }&quot; placeholder=&quot;{ placeholderText }&quot; button_label=&quot;{ searchButtonText }&quot; facets=&quot;{ displayFacets }&quot; combo_box=&quot;{ displayFacetsCombo }&quot;]
				</div>
			</div>
		);
	},

	save( { attributes, className } ) {
		const { placeholderText, postType, displayFacets, displayFacetsCombo, searchButtonText } = attributes;

		let comboBox;

		if ( ! displayFacets.trim().length==0 && displayFacetsCombo === 'true' ) {
			comboBox = `combo_box="${displayFacetsCombo}"`
		}

		return (
			<div id="search-block" className={ className }>
				<div className="search-block">
				[lsx_search_form front engine=&quot;{ postType }&quot; placeholder=&quot;{ placeholderText }&quot; button_label=&quot;{ searchButtonText }&quot; facets=&quot;{ displayFacets }&quot; { comboBox } ]
				</div>
			</div>
		);
	},
} );
