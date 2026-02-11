/**
 * NL Design System Theme - Admin Settings JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
	const tokenSetInputs = document.querySelectorAll('input[name="nldesign-token-set"]');
	const hideSloganCheckbox = document.getElementById('nldesign-hide-slogan');
	const previewBox = document.querySelector('.nldesign-preview-box');

	// Token set color mappings for preview
	const tokenSetColors = {
		rijkshuisstijl: {
			primary: '#154273',
			primaryHover: '#1d5499',
			primaryText: '#ffffff'
		},
		utrecht: {
			primary: '#cc0000',
			primaryHover: '#a30000',
			primaryText: '#ffffff'
		},
		amsterdam: {
			primary: '#ec0000',
			primaryHover: '#b30000',
			primaryText: '#ffffff'
		},
		denhaag: {
			primary: '#1a7a3e',
			primaryHover: '#156633',
			primaryText: '#ffffff'
		},
		rotterdam: {
			primary: '#00811f',
			primaryHover: '#006619',
			primaryText: '#ffffff'
		}
	};

	// Update preview when token set changes
	function updatePreview(tokenSet) {
		const colors = tokenSetColors[tokenSet];
		if (!colors || !previewBox) return;

		const header = previewBox.querySelector('.nldesign-preview-header');
		const primaryButton = previewBox.querySelector('.nldesign-preview-button.primary');

		if (header) {
			header.style.backgroundColor = colors.primary;
		}

		if (primaryButton) {
			primaryButton.style.backgroundColor = colors.primary;
			primaryButton.style.borderColor = colors.primary;
			primaryButton.style.color = colors.primaryText;

			primaryButton.onmouseenter = function() {
				this.style.backgroundColor = colors.primaryHover;
			};
			primaryButton.onmouseleave = function() {
				this.style.backgroundColor = colors.primary;
			};
		}
	}

	// Handle token set selection
	tokenSetInputs.forEach(function(input) {
		input.addEventListener('change', function() {
			const tokenSet = this.value;

			// Update preview
			updatePreview(tokenSet);

			// Save to server
			saveTokenSet(tokenSet);
		});

		// Set initial preview for checked item
		if (input.checked) {
			updatePreview(input.value);
		}
	});

	// Save token set to server
	function saveTokenSet(tokenSet) {
		const url = OC.generateUrl('/apps/nldesign/settings/tokenset');

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({ tokenSet: tokenSet })
		})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'ok') {
				OC.Notification.showTemporary(t('nldesign', 'Theme updated successfully. Reload the page to see changes.'));
			} else {
				OC.Notification.showTemporary(t('nldesign', 'Failed to update theme.'));
			}
		})
		.catch(error => {
			console.error('Error saving token set:', error);
			OC.Notification.showTemporary(t('nldesign', 'Failed to update theme.'));
		});
	}

	// Handle hide slogan checkbox
	if (hideSloganCheckbox) {
		hideSloganCheckbox.addEventListener('change', function() {
			const hideSlogan = this.checked;
			saveSloganSetting(hideSlogan);
		});
	}

	// Save hide slogan setting to server
	function saveSloganSetting(hideSlogan) {
		const url = OC.generateUrl('/apps/nldesign/settings/slogan');

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({ hideSlogan: hideSlogan })
		})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'ok') {
				OC.Notification.showTemporary(t('nldesign', 'Setting saved successfully. Reload the login page to see changes.'));
			} else {
				OC.Notification.showTemporary(t('nldesign', 'Failed to save setting.'));
			}
		})
		.catch(error => {
			console.error('Error saving slogan setting:', error);
			OC.Notification.showTemporary(t('nldesign', 'Failed to save setting.'));
		});
	}
});
