module.exports = {
	extends: 'stylelint-config-recommended',
	rules: {
		// Override styles intentionally repeat Nextcloud selectors at increasing
		// specificity across compatibility layers; source order is the contract.
		'no-descending-specificity': null,
	},
}
