module.exports = {
	extends: 'stylelint-config-recommended',
	rules: {
		// This is a theming/override app — descending specificity is intentional
		// when overriding Nextcloud's built-in styles.
		'no-descending-specificity': null,
		// Override stylesheets intentionally use shorthand after longhand
		// to reset properties in specific contexts.
		'declaration-block-no-shorthand-property-overrides': null,
	},
}
