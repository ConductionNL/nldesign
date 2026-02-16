/**
 * NL Design System Theme - Admin Settings JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
	var settingsEl = document.getElementById('nldesign-settings');
	var tokenSetSelect = document.getElementById('nldesign-token-set-select');
	var hideSloganCheckbox = document.getElementById('nldesign-hide-slogan');
	var previewBox = document.querySelector('.nldesign-preview-box');

	// Parse token sets data from the template
	var tokenSetsData = {};
	try {
		var tokenSets = JSON.parse(settingsEl.getAttribute('data-token-sets') || '[]');
		tokenSets.forEach(function(ts) {
			tokenSetsData[ts.id] = ts;
		});
	} catch (e) {
		console.error('Failed to parse token sets data:', e);
	}

	// Token set color mappings for preview
	var tokenSetColors = {
		rijkshuisstijl: { primary: '#154273', primaryHover: '#162f50', primaryText: '#ffffff' },
		utrecht: { primary: '#24578F', primaryHover: '#1F4B7A', primaryText: '#ffffff' },
		amsterdam: { primary: '#004699', primaryHover: '#003677', primaryText: '#ffffff' },
		denhaag: { primary: '#1a7a3e', primaryHover: '#156633', primaryText: '#ffffff' },
		rotterdam: { primary: '#00811f', primaryHover: '#006E32', primaryText: '#ffffff' },
		vng: { primary: '#003865', primaryHover: '#026596', primaryText: '#ffffff' },
		leiden: { primary: '#d62410', primaryHover: '#b01d0d', primaryText: '#ffffff' },
		noaberkracht: { primary: '#4376fc', primaryHover: '#2b5fd4', primaryText: '#ffffff' }
	};

	// Update preview when token set changes
	function updatePreview(tokenSet) {
		var colors = tokenSetColors[tokenSet];
		if (!colors || !previewBox) return;

		var header = previewBox.querySelector('.nldesign-preview-header');
		var primaryButton = previewBox.querySelector('.nldesign-preview-button.primary');

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

	// Handle token set dropdown selection
	if (tokenSetSelect) {
		tokenSetSelect.addEventListener('change', function() {
			var tokenSet = this.value;

			// Update preview optimistically
			updatePreview(tokenSet);

			// Save to server
			saveTokenSet(tokenSet);
		});

		// Set initial preview for selected item
		updatePreview(tokenSetSelect.value);
	}

	// Save token set to server
	function saveTokenSet(tokenSet) {
		var url = OC.generateUrl('/apps/nldesign/settings/tokenset');

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({ tokenSet: tokenSet })
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.status === 'ok') {
				OC.Notification.showTemporary(t('nldesign', 'Theme updated successfully. Reload the page to see changes.'));

				// Check if this token set has theming metadata
				var tsData = tokenSetsData[tokenSet];
				if (tsData && tsData.theming) {
					checkAndShowThemingDialog(tsData);
				}
			} else {
				OC.Notification.showTemporary(t('nldesign', 'Failed to update theme.'));
			}
		})
		.catch(function(error) {
			console.error('Error saving token set:', error);
			OC.Notification.showTemporary(t('nldesign', 'Failed to update theme.'));
		});
	}

	// Fetch current NC theming values and show dialog if they differ
	function checkAndShowThemingDialog(tokenSetData) {
		var url = OC.generateUrl('/apps/nldesign/settings/theming');

		fetch(url, {
			headers: {
				'requesttoken': OC.requestToken
			}
		})
		.then(function(response) { return response.json(); })
		.then(function(currentTheming) {
			var proposed = tokenSetData.theming;
			var diffs = [];

			if (proposed.primary_color && proposed.primary_color.toLowerCase() !== currentTheming.primary_color.toLowerCase()) {
				diffs.push({
					label: t('nldesign', 'Primary color'),
					key: 'primary_color',
					current: currentTheming.primary_color,
					proposed: proposed.primary_color
				});
			}

			if (proposed.background_color && proposed.background_color.toLowerCase() !== currentTheming.background_color.toLowerCase()) {
				diffs.push({
					label: t('nldesign', 'Background color'),
					key: 'background_color',
					current: currentTheming.background_color,
					proposed: proposed.background_color
				});
			}

			if (proposed.logo) {
				diffs.push({
					label: t('nldesign', 'Logo'),
					key: 'logo',
					current: currentTheming.has_custom_logo ? t('nldesign', '(custom logo)') : t('nldesign', '(default)'),
					proposed: proposed.logo.split('/').pop()
				});
			}

			if (proposed.background) {
				diffs.push({
					label: t('nldesign', 'Background image'),
					key: 'background',
					current: currentTheming.has_custom_background ? t('nldesign', '(custom)') : t('nldesign', '(default)'),
					proposed: proposed.background.split('/').pop()
				});
			}

			if (diffs.length > 0) {
				showThemingDialog(tokenSetData, currentTheming, proposed, diffs);
			}
		})
		.catch(function(error) {
			console.error('Error fetching theming values:', error);
		});
	}

	// Show the theming sync dialog
	function showThemingDialog(tokenSetData, currentTheming, proposed, diffs) {
		// Remove any existing dialog
		var existing = document.getElementById('nldesign-theming-dialog-overlay');
		if (existing) existing.remove();

		var tokenSetName = tokenSetData.name;

		// Build comparison rows
		var rows = '';
		diffs.forEach(function(diff) {
			var currentDisplay = diff.current || '';
			var proposedDisplay = diff.proposed || '';

			if (diff.key === 'primary_color' || diff.key === 'background_color') {
				currentDisplay = '<span class="nldesign-dialog-swatch" style="background:' + escapeHtml(diff.current) + '"></span> ' + escapeHtml(diff.current);
				proposedDisplay = '<span class="nldesign-dialog-swatch" style="background:' + escapeHtml(diff.proposed) + '"></span> ' + escapeHtml(diff.proposed);
			} else {
				currentDisplay = escapeHtml(currentDisplay);
				proposedDisplay = escapeHtml(proposedDisplay);
			}

			rows += '<tr>'
				+ '<td>' + escapeHtml(diff.label) + '</td>'
				+ '<td>' + currentDisplay + '</td>'
				+ '<td>' + proposedDisplay + '</td>'
				+ '</tr>';
		});

		// Build preview boxes
		var currentBg = currentTheming.background_color || '#0082c9';
		var proposedBg = proposed.background_color || currentBg;
		var currentLogoUrl = currentTheming.logo_url || '';
		var proposedLogoPath = proposed.logo ? OC.linkTo('nldesign', proposed.logo) : '';

		var dialogHtml = ''
			+ '<div id="nldesign-theming-dialog-overlay" class="nldesign-dialog-overlay">'
			+ '  <div class="nldesign-dialog">'
			+ '    <h3>' + escapeHtml(t('nldesign', 'Update Nextcloud theming to match {name}?').replace('{name}', tokenSetName)) + '</h3>'
			+ '    <div class="nldesign-dialog-previews">'
			+ '      <div class="nldesign-dialog-preview-col">'
			+ '        <span class="nldesign-dialog-preview-label">' + escapeHtml(t('nldesign', 'Current')) + '</span>'
			+ '        <div class="nldesign-dialog-preview-box" style="background-color:' + escapeHtml(currentBg) + ';' + (currentTheming.has_custom_background && currentTheming.background_url ? 'background-image:url(' + escapeHtml(currentTheming.background_url) + ');background-size:cover;' : '') + '">'
			+ (currentLogoUrl ? '          <img class="nldesign-dialog-preview-logo" src="' + escapeHtml(currentLogoUrl) + '" alt="Current logo">' : '')
			+ '        </div>'
			+ '      </div>'
			+ '      <div class="nldesign-dialog-preview-col">'
			+ '        <span class="nldesign-dialog-preview-label">' + escapeHtml(t('nldesign', 'Proposed')) + '</span>'
			+ '        <div class="nldesign-dialog-preview-box" style="background-color:' + escapeHtml(proposedBg) + ';">'
			+ (proposedLogoPath ? '          <img class="nldesign-dialog-preview-logo" src="' + escapeHtml(proposedLogoPath) + '" alt="Proposed logo">' : (currentLogoUrl ? '          <img class="nldesign-dialog-preview-logo" src="' + escapeHtml(currentLogoUrl) + '" alt="Current logo">' : ''))
			+ '        </div>'
			+ '      </div>'
			+ '    </div>'
			+ '    <table class="nldesign-dialog-table">'
			+ '      <thead><tr><th>' + escapeHtml(t('nldesign', 'Setting')) + '</th><th>' + escapeHtml(t('nldesign', 'Current')) + '</th><th>' + escapeHtml(t('nldesign', 'Proposed')) + '</th></tr></thead>'
			+ '      <tbody>' + rows + '</tbody>'
			+ '    </table>'
			+ '    <p class="nldesign-dialog-hint">' + escapeHtml(t('nldesign', 'Only values that differ are shown. Items without a proposed value are left unchanged.')) + '</p>'
			+ '    <div class="nldesign-dialog-actions">'
			+ '      <button class="nldesign-dialog-cancel">' + escapeHtml(t('nldesign', 'Cancel')) + '</button>'
			+ '      <button class="nldesign-dialog-confirm">' + escapeHtml(t('nldesign', 'Update theming')) + '</button>'
			+ '    </div>'
			+ '  </div>'
			+ '</div>';

		document.body.insertAdjacentHTML('beforeend', dialogHtml);

		var overlay = document.getElementById('nldesign-theming-dialog-overlay');

		// Cancel button
		overlay.querySelector('.nldesign-dialog-cancel').addEventListener('click', function() {
			overlay.remove();
		});

		// Close on overlay click
		overlay.addEventListener('click', function(e) {
			if (e.target === overlay) {
				overlay.remove();
			}
		});

		// Confirm button
		overlay.querySelector('.nldesign-dialog-confirm').addEventListener('click', function() {
			var btn = this;
			btn.disabled = true;
			btn.textContent = t('nldesign', 'Updating...');

			var payload = {};
			diffs.forEach(function(diff) {
				if (diff.key === 'primary_color' || diff.key === 'background_color') {
					payload[diff.key] = diff.proposed;
				} else if (diff.key === 'logo' || diff.key === 'background') {
					payload[diff.key] = proposed[diff.key];
				}
			});

			var url = OC.generateUrl('/apps/nldesign/settings/theming');
			fetch(url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'requesttoken': OC.requestToken
				},
				body: Object.keys(payload).map(function(key) {
					return encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]);
				}).join('&')
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				overlay.remove();
				if (data.status === 'ok') {
					OC.Notification.showTemporary(t('nldesign', 'Nextcloud theming updated successfully. Reloading page...'));
					setTimeout(function() {
						window.location.reload();
					}, 1500);
				} else {
					OC.Notification.showTemporary(t('nldesign', 'Failed to update Nextcloud theming: ') + (data.error || ''));
				}
			})
			.catch(function(error) {
				overlay.remove();
				console.error('Error updating theming:', error);
				OC.Notification.showTemporary(t('nldesign', 'Failed to update Nextcloud theming.'));
			});
		});
	}

	// Escape HTML to prevent XSS
	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Handle hide slogan checkbox
	if (hideSloganCheckbox) {
		hideSloganCheckbox.addEventListener('change', function() {
			var hideSlogan = this.checked;
			saveSloganSetting(hideSlogan);
		});
	}

	// Handle show menu labels checkbox
	var showMenuLabelsCheckbox = document.getElementById('nldesign-show-menu-labels');
	if (showMenuLabelsCheckbox) {
		showMenuLabelsCheckbox.addEventListener('change', function() {
			var showMenuLabels = this.checked;
			saveMenuLabelsSetting(showMenuLabels);
		});
	}

	// Save hide slogan setting to server
	function saveSloganSetting(hideSlogan) {
		var url = OC.generateUrl('/apps/nldesign/settings/slogan');

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({ hideSlogan: hideSlogan })
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.status === 'ok') {
				OC.Notification.showTemporary(t('nldesign', 'Setting saved successfully. Reload the login page to see changes.'));
			} else {
				OC.Notification.showTemporary(t('nldesign', 'Failed to save setting.'));
			}
		})
		.catch(function(error) {
			console.error('Error saving slogan setting:', error);
			OC.Notification.showTemporary(t('nldesign', 'Failed to save setting.'));
		});
	}

	// Save show menu labels setting to server.
	function saveMenuLabelsSetting(showMenuLabels) {
		var url = OC.generateUrl('/apps/nldesign/settings/menulabels');

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({ showMenuLabels: showMenuLabels })
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.status === 'ok') {
				OC.Notification.showTemporary(t('nldesign', 'Setting saved successfully. Reload the page to see changes.'));
			} else {
				OC.Notification.showTemporary(t('nldesign', 'Failed to save setting.'));
			}
		})
		.catch(function(error) {
			console.error('Error saving menu labels setting:', error);
			OC.Notification.showTemporary(t('nldesign', 'Failed to save setting.'));
		});
	}
});
