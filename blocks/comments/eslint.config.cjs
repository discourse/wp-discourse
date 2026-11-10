const config = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...config,
	{
		settings: {
			// WordPress supplies these modules; webpack extracts them as externals.
			'import/core-modules': [
				'@wordpress/block-editor',
				'@wordpress/blocks',
				'@wordpress/i18n',
			],
		},
	},
];
