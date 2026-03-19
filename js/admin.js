/**
 * NL Design System Theme - Admin Settings JavaScript
 */

(function nldesignAdminInit() {
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', nldesignAdminMain);
} else {
	nldesignAdminMain();
}
function nldesignAdminMain() {
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
		nextcloud: { primary: '#0082c9', primaryHover: '#006fad', primaryText: '#ffffff' },
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

	// Design system display names
	var designSystemNames = {
		'none': 'Stock Nextcloud',
		'nldesign': 'NL Design System'
	};

	// Update the design system badge for the selected token set
	function updateDesignSystemBadge(tokenSetId) {
		var badge = document.getElementById('nldesign-design-system-badge');
		if (!badge) return;

		var option = tokenSetSelect ? tokenSetSelect.querySelector('option[value="' + tokenSetId + '"]') : null;
		var dsId = option ? (option.getAttribute('data-design-system') || 'nldesign') : 'nldesign';
		var dsName = designSystemNames[dsId] || dsId;

		badge.textContent = dsName;
		badge.className = 'nldesign-badge' + (dsId === 'none' ? ' nldesign-badge--stock' : ' nldesign-badge--system');
	}

	// Handle token set dropdown selection — open apply dialog first
	if (tokenSetSelect) {
		tokenSetSelect.addEventListener('change', function() {
			var newTokenSet  = this.value;
			var prevTokenSet = this.dataset.previousValue || this.options[this.selectedIndex === 0 ? 0 : this.selectedIndex].value;

			// Store previous value so we can revert on Cancel.
			this.dataset.previousValue = newTokenSet;

			// Update preview and design system badge optimistically
			updatePreview(newTokenSet);
			updateDesignSystemBadge(newTokenSet);

			// Open the token overrides apply dialog.
			openTokenSetApplyDialog(newTokenSet, prevTokenSet);
		});

		// Set initial preview for selected item and remember initial value.
		updatePreview(tokenSetSelect.value);
		updateDesignSystemBadge(tokenSetSelect.value);
		tokenSetSelect.dataset.previousValue = tokenSetSelect.value;
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

	/* ==========================================================================
	 * TOKEN EDITOR PANEL
	 * ========================================================================== */

	// Holds the in-memory state of the editor: token name → { resolved, custom, current, isDirty }
	var tokenEditorState = {};
	// Registry from server: token name → { tab, type, label }
	var tokenRegistry    = {};
	// Tab labels from server: tab id → display label
	var tokenTabLabels   = {};

	/**
	 * Initialise and mount the token editor panel into #nldesign-token-editor.
	 */
	function initTokenEditor() {
		var container = document.getElementById('nldesign-token-editor');
		if (container === null) {
			return;
		}

		fetch(OC.generateUrl('/apps/nldesign/settings/overrides'), {
			headers: { 'requesttoken': OC.requestToken }
		})
		.then(function(r) { return r.json(); })
		.then(function(data) {
			tokenRegistry  = data.registry  || {};
			tokenTabLabels = data.tabs       || {};
			var overrides  = data.overrides  || {};

			// Read resolved values from the live CSS stack.
			var rootStyle = getComputedStyle(document.documentElement);
			Object.keys(tokenRegistry).forEach(function(name) {
				var resolved   = rootStyle.getPropertyValue(name).trim();
				var overridden = overrides[name] !== undefined ? overrides[name] : null;
				tokenEditorState[name] = {
					resolved: resolved,
					custom:   overridden,
					current:  overridden !== null ? overridden : resolved,
					isDirty:  false
				};
			});

			renderTokenEditor(container, overrides);
		})
		.catch(function(err) {
			console.error('Failed to load token editor:', err);
			container.innerHTML = '<p class="settings-hint">' + escapeHtml(t('nldesign', 'Could not load token editor.')) + '</p>';
		});
	}

	/**
	 * Render the full token editor panel into the given container element.
	 */
	function renderTokenEditor(container, overrides) {
		var grouped  = {};
		Object.keys(tokenRegistry).forEach(function(name) {
			var meta = tokenRegistry[name];
			if (grouped[meta.tab] === undefined) {
				grouped[meta.tab] = [];
			}
			grouped[meta.tab].push(name);
		});

		var tabOrder    = ['login', 'content', 'status', 'typography'];
		var tabsHtml    = '';
		var panelsHtml  = '';
		var isFirst     = true;

		tabOrder.forEach(function(tabId) {
			if (grouped[tabId] === undefined) {
				return;
			}
			var label       = escapeHtml(tokenTabLabels[tabId] || tabId);
			var activeClass = isFirst ? ' active' : '';
			tabsHtml   += '<button class="nldesign-tab-btn' + activeClass + '" data-tab="' + escapeHtml(tabId) + '">' + label + '</button>';
			var rowsHtml = '';
			grouped[tabId].forEach(function(name) {
				rowsHtml += buildTokenRow(name, overrides[name] !== undefined ? overrides[name] : null);
			});
			panelsHtml += '<div class="nldesign-tab-panel' + activeClass + '" data-panel="' + escapeHtml(tabId) + '">' + rowsHtml + '</div>';
			isFirst = false;
		});

		container.innerHTML = ''
			+ '<div class="nldesign-token-editor">'
			+   '<div class="nldesign-token-editor-header">'
			+     '<h3>' + escapeHtml(t('nldesign', 'Custom Token Overrides')) + '</h3>'
			+     '<div class="nldesign-token-editor-actions">'
			+       '<button class="nldesign-btn nldesign-btn--small" id="nldesign-export-btn">' + escapeHtml(t('nldesign', 'Download')) + '</button>'
			+       '<label class="nldesign-btn nldesign-btn--small" style="cursor:pointer">'
			+         escapeHtml(t('nldesign', 'Upload'))
			+         '<input type="file" id="nldesign-import-input" accept=".css" style="display:none">'
			+       '</label>'
			+     '</div>'
			+   '</div>'
			+   '<div class="nldesign-tabs">' + tabsHtml + '</div>'
			+   panelsHtml
			+   '<div class="nldesign-save-bar">'
			+     '<span class="nldesign-save-status" id="nldesign-save-status"></span>'
			+     '<button class="nldesign-btn nldesign-btn--primary" id="nldesign-save-btn">' + escapeHtml(t('nldesign', 'Save overrides')) + '</button>'
			+   '</div>'
			+ '</div>'
			+ '<div id="nldesign-import-result" class="nldesign-import-result" style="display:none"></div>';

		container.querySelectorAll('.nldesign-tab-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				container.querySelectorAll('.nldesign-tab-btn').forEach(function(b) { b.classList.remove('active'); });
				container.querySelectorAll('.nldesign-tab-panel').forEach(function(p) { p.classList.remove('active'); });
				btn.classList.add('active');
				container.querySelector('.nldesign-tab-panel[data-panel="' + btn.dataset.tab + '"]').classList.add('active');
			});
		});

		wireTokenRows(container);

		document.getElementById('nldesign-save-btn').addEventListener('click', saveOverrides);
		document.getElementById('nldesign-export-btn').addEventListener('click', exportOverrides);
		document.getElementById('nldesign-import-input').addEventListener('change', function(e) {
			var file = e.target.files[0];
			if (file === undefined) {
				return;
			}
			importOverrides(file);
			e.target.value = '';
		});
	}

	/**
	 * Build HTML for a single token row.
	 */
	function buildTokenRow(name, customVal) {
		var meta       = tokenRegistry[name];
		var state      = tokenEditorState[name];
		var displayVal = state ? state.current : (customVal !== null ? customVal : '');
		var isCustom   = customVal !== null && customVal !== undefined;

		var badgeHtml = isCustom ? '<span class="nldesign-token-custom-badge" title="' + escapeHtml(t('nldesign', 'Custom value')) + '"></span>' : '';

		var inputHtml = '';
		if (meta.type === 'color') {
			var pickerVal = normaliseColorForPicker(displayVal);
			inputHtml = '<div class="nldesign-color-input-wrap">'
				+ '<input type="color" class="nldesign-color-picker" data-token="' + escapeHtml(name) + '" value="' + escapeHtml(pickerVal) + '">'
				+ '<input type="text" class="nldesign-color-text" data-token="' + escapeHtml(name) + '" value="' + escapeHtml(displayVal) + '">'
				+ '</div>';
		} else {
			inputHtml = '<input type="text" class="nldesign-text-input" data-token="' + escapeHtml(name) + '" value="' + escapeHtml(displayVal) + '">';
		}

		return '<div class="nldesign-token-row" data-token-row="' + escapeHtml(name) + '">'
			+ '<div class="nldesign-token-label-wrap">'
			+   '<span class="nldesign-token-label">' + escapeHtml(meta.label) + badgeHtml + '</span>'
			+   '<span class="nldesign-token-name">' + escapeHtml(name) + '</span>'
			+ '</div>'
			+ inputHtml
			+ '<button class="nldesign-btn nldesign-btn--small nldesign-reset-btn" data-token="' + escapeHtml(name) + '" title="' + escapeHtml(t('nldesign', 'Reset to default')) + '">↺</button>'
			+ '</div>';
	}

	/**
	 * Wire event listeners on all token rows inside a container.
	 */
	function wireTokenRows(container) {
		container.querySelectorAll('.nldesign-color-picker').forEach(function(picker) {
			picker.addEventListener('input', function() {
				var name      = picker.dataset.token;
				var value     = picker.value;
				var textField = container.querySelector('.nldesign-color-text[data-token="' + name + '"]');
				if (textField !== null) {
					textField.value = value;
				}
				applyLivePreview(name, value);
				markDirty(name, value, container);
			});
		});

		container.querySelectorAll('.nldesign-color-text').forEach(function(field) {
			field.addEventListener('input', function() {
				var name   = field.dataset.token;
				var value  = field.value.trim();
				var picker = container.querySelector('.nldesign-color-picker[data-token="' + name + '"]');
				if (picker !== null && /^#[0-9a-fA-F]{6}$/.test(value) === true) {
					picker.value = value;
				}
				applyLivePreview(name, value);
				markDirty(name, value, container);
			});
		});

		container.querySelectorAll('.nldesign-text-input').forEach(function(field) {
			field.addEventListener('input', function() {
				var name  = field.dataset.token;
				var value = field.value.trim();
				applyLivePreview(name, value);
				markDirty(name, value, container);
			});
		});

		container.querySelectorAll('.nldesign-reset-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var name  = btn.dataset.token;
				var state = tokenEditorState[name];
				if (state === undefined) {
					return;
				}
				var defaultVal = state.resolved;
				var textField  = container.querySelector('.nldesign-color-text[data-token="' + name + '"], .nldesign-text-input[data-token="' + name + '"]');
				var picker     = container.querySelector('.nldesign-color-picker[data-token="' + name + '"]');
				if (textField !== null) {
					textField.value = defaultVal;
				}
				if (picker !== null) {
					picker.value = normaliseColorForPicker(defaultVal);
				}
				var row = container.querySelector('[data-token-row="' + name + '"]');
				if (row !== null) {
					var badge = row.querySelector('.nldesign-token-custom-badge');
					if (badge !== null) {
						badge.remove();
					}
				}
				document.documentElement.style.removeProperty(name);
				tokenEditorState[name].current = defaultVal;
				tokenEditorState[name].custom  = null;
				tokenEditorState[name].isDirty = true;
				updateSaveStatus();
			});
		});
	}

	function applyLivePreview(name, value) {
		if (value.trim() === '') {
			document.documentElement.style.removeProperty(name);
		} else {
			document.documentElement.style.setProperty(name, value);
		}
	}

	function markDirty(name, value, container) {
		if (tokenEditorState[name] === undefined) {
			tokenEditorState[name] = { resolved: '', custom: null, current: value, isDirty: true };
		}
		tokenEditorState[name].current = value;
		tokenEditorState[name].isDirty = true;

		var row   = container.querySelector('[data-token-row="' + name + '"]');
		var label = row !== null ? row.querySelector('.nldesign-token-label') : null;
		if (label !== null && label.querySelector('.nldesign-token-custom-badge') === null) {
			label.insertAdjacentHTML('beforeend', '<span class="nldesign-token-custom-badge"></span>');
		}
		updateSaveStatus();
	}

	function updateSaveStatus() {
		var statusEl   = document.getElementById('nldesign-save-status');
		if (statusEl === null) {
			return;
		}
		var dirtyCount = Object.keys(tokenEditorState).filter(function(k) { return tokenEditorState[k].isDirty === true; }).length;
		statusEl.textContent = dirtyCount > 0 ? t('nldesign', 'Unsaved changes') : '';
	}

	function saveOverrides() {
		var overrides = {};
		Object.keys(tokenEditorState).forEach(function(name) {
			var state = tokenEditorState[name];
			var value = state.current.trim();
			if (value !== '' && value !== state.resolved) {
				overrides[name] = value;
			}
		});

		var btn = document.getElementById('nldesign-save-btn');
		if (btn !== null) {
			btn.disabled = true;
		}

		fetch(OC.generateUrl('/apps/nldesign/settings/overrides'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({ overrides: overrides })
		})
		.then(function(r) { return r.json(); })
		.then(function(data) {
			if (btn !== null) {
				btn.disabled = false;
			}
			if (data.status === 'ok') {
				Object.keys(tokenEditorState).forEach(function(k) { tokenEditorState[k].isDirty = false; });
				updateSaveStatus();
				OC.Notification.showTemporary(t('nldesign', 'Token overrides saved.'));
			} else {
				OC.Notification.showTemporary(t('nldesign', 'Failed to save overrides: ') + (data.error || ''));
			}
		})
		.catch(function(err) {
			if (btn !== null) {
				btn.disabled = false;
			}
			console.error('Error saving overrides:', err);
			OC.Notification.showTemporary(t('nldesign', 'Failed to save overrides.'));
		});
	}

	function exportOverrides() {
		var a      = document.createElement('a');
		a.href     = OC.generateUrl('/apps/nldesign/settings/overrides/export');
		a.download = 'custom-overrides.css';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
	}

	function importOverrides(file) {
		var formData = new FormData();
		formData.append('file', file);

		fetch(OC.generateUrl('/apps/nldesign/settings/overrides/import'), {
			method: 'POST',
			headers: { 'requesttoken': OC.requestToken },
			body: formData
		})
		.then(function(r) { return r.json(); })
		.then(function(data) {
			var resultEl = document.getElementById('nldesign-import-result');
			if (data.status === 'ok') {
				if (resultEl !== null) {
					resultEl.textContent = t('nldesign', '{imported} tokens imported, {skipped} tokens skipped (not recognized)')
						.replace('{imported}', data.imported)
						.replace('{skipped}', data.skipped);
					resultEl.style.display = 'block';
					setTimeout(function() { resultEl.style.display = 'none'; }, 8000);
				}
				initTokenEditor();
			} else {
				OC.Notification.showTemporary(t('nldesign', 'Import failed: ') + (data.error || ''));
			}
		})
		.catch(function(err) {
			console.error('Error importing overrides:', err);
			OC.Notification.showTemporary(t('nldesign', 'Import failed.'));
		});
	}

	/* ==========================================================================
	 * TOKEN SET APPLY DIALOG
	 * ========================================================================== */

	function openTokenSetApplyDialog(newTokenSetId, prevTokenSetId) {
		fetch(OC.generateUrl('/apps/nldesign/settings/tokenset-preview/' + encodeURIComponent(newTokenSetId)), {
			headers: { 'requesttoken': OC.requestToken }
		})
		.then(function(r) { return r.json(); })
		.then(function(data) {
			if (data.error !== undefined) {
				saveTokenSet(newTokenSetId);
				return;
			}
			var newValues = data.resolved || {};
			var rootStyle = getComputedStyle(document.documentElement);
			var changes   = [];
			Object.keys(newValues).forEach(function(name) {
				var currentVal = rootStyle.getPropertyValue(name).trim();
				var newVal     = newValues[name].trim();
				if (currentVal !== newVal && newVal !== '') {
					changes.push({ name: name, current: currentVal, newVal: newVal });
				}
			});
			if (changes.length === 0) {
				saveTokenSet(newTokenSetId);
				return;
			}
			showApplyDialog(newTokenSetId, prevTokenSetId, changes);
		})
		.catch(function(err) {
			console.error('Error fetching token set preview:', err);
			saveTokenSet(newTokenSetId);
		});
	}

	function showApplyDialog(newTokenSetId, prevTokenSetId, changes) {
		var existing = document.getElementById('nldesign-apply-dialog-overlay');
		if (existing !== null) {
			existing.remove();
		}

		var rowsHtml = '';
		changes.forEach(function(change) {
			var meta    = tokenRegistry[change.name] || { label: change.name, type: 'text' };
			var isColor = meta.type === 'color';
			var currentDisp = isColor
				? '<span class="nldesign-apply-swatch" style="background:' + escapeHtml(change.current) + '"></span>' + escapeHtml(change.current)
				: escapeHtml(change.current);
			var newDisp = isColor
				? '<span class="nldesign-apply-swatch" style="background:' + escapeHtml(change.newVal) + '"></span>' + escapeHtml(change.newVal)
				: escapeHtml(change.newVal);
			rowsHtml += '<tr>'
				+ '<td><input type="checkbox" class="nldesign-apply-check" data-token="' + escapeHtml(change.name) + '" checked></td>'
				+ '<td><span title="' + escapeHtml(change.name) + '">' + escapeHtml(meta.label) + '</span></td>'
				+ '<td>' + currentDisp + '</td>'
				+ '<td>' + newDisp + '</td>'
				+ '</tr>';
		});

		var html = '<div id="nldesign-apply-dialog-overlay" class="nldesign-dialog-overlay">'
			+ '<div class="nldesign-dialog">'
			+ '<h3>' + escapeHtml(t('nldesign', 'Apply token set: {name}').replace('{name}', newTokenSetId)) + '</h3>'
			+ '<p class="settings-hint">' + escapeHtml(t('nldesign', 'These values would change. Check which ones to apply to your custom overrides.')) + '</p>'
			+ '<div style="margin-bottom:8px">'
			+ '<button class="nldesign-apply-dialog-toggle" id="nldesign-apply-select-all">' + escapeHtml(t('nldesign', 'Select all')) + '</button>'
			+ ' / '
			+ '<button class="nldesign-apply-dialog-toggle" id="nldesign-apply-deselect-all">' + escapeHtml(t('nldesign', 'Deselect all')) + '</button>'
			+ '</div>'
			+ '<table class="nldesign-apply-dialog-table"><thead><tr>'
			+ '<th></th>'
			+ '<th>' + escapeHtml(t('nldesign', 'Token')) + '</th>'
			+ '<th>' + escapeHtml(t('nldesign', 'Current')) + '</th>'
			+ '<th>' + escapeHtml(t('nldesign', 'New')) + '</th>'
			+ '</tr></thead><tbody>' + rowsHtml + '</tbody></table>'
			+ '<div class="nldesign-dialog-actions">'
			+ '<button class="nldesign-dialog-cancel">' + escapeHtml(t('nldesign', 'Cancel')) + '</button>'
			+ '<button class="nldesign-dialog-confirm">' + escapeHtml(t('nldesign', 'Apply selected')) + '</button>'
			+ '</div>'
			+ '</div>'
			+ '</div>';

		document.body.insertAdjacentHTML('beforeend', html);
		var overlay = document.getElementById('nldesign-apply-dialog-overlay');

		function updateApplyPreview() {
			changes.forEach(function(change) {
				var cb = overlay.querySelector('.nldesign-apply-check[data-token="' + change.name + '"]');
				if (cb !== null && cb.checked === true) {
					document.documentElement.style.setProperty(change.name, change.newVal);
				} else {
					document.documentElement.style.setProperty(change.name, change.current);
				}
			});
		}

		overlay.querySelectorAll('.nldesign-apply-check').forEach(function(cb) {
			cb.addEventListener('change', updateApplyPreview);
		});
		updateApplyPreview();

		document.getElementById('nldesign-apply-select-all').addEventListener('click', function() {
			overlay.querySelectorAll('.nldesign-apply-check').forEach(function(cb) { cb.checked = true; });
			updateApplyPreview();
		});
		document.getElementById('nldesign-apply-deselect-all').addEventListener('click', function() {
			overlay.querySelectorAll('.nldesign-apply-check').forEach(function(cb) { cb.checked = false; });
			updateApplyPreview();
		});

		function cancelDialog() {
			changes.forEach(function(c) { document.documentElement.style.removeProperty(c.name); });
			if (tokenSetSelect !== null) {
				tokenSetSelect.value                 = prevTokenSetId;
				tokenSetSelect.dataset.previousValue = prevTokenSetId;
				updatePreview(prevTokenSetId);
			}
			overlay.remove();
		}

		overlay.querySelector('.nldesign-dialog-cancel').addEventListener('click', cancelDialog);
		overlay.addEventListener('click', function(e) {
			if (e.target === overlay) {
				cancelDialog();
			}
		});

		overlay.querySelector('.nldesign-dialog-confirm').addEventListener('click', function() {
			var btn      = this;
			btn.disabled = true;
			btn.textContent = t('nldesign', 'Applying…');

			var toApply = {};
			overlay.querySelectorAll('.nldesign-apply-check').forEach(function(cb) {
				if (cb.checked === true) {
					var change = changes.find(function(c) { return c.name === cb.dataset.token; });
					if (change !== undefined) {
						toApply[cb.dataset.token] = change.newVal;
					}
				}
			});

			fetch(OC.generateUrl('/apps/nldesign/settings/overrides'), {
				headers: { 'requesttoken': OC.requestToken }
			})
			.then(function(r) { return r.json(); })
			.then(function(existingData) {
				var merged = Object.assign({}, existingData.overrides || {}, toApply);
				return fetch(OC.generateUrl('/apps/nldesign/settings/overrides'), {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken },
					body: JSON.stringify({ overrides: merged })
				});
			})
			.then(function(r) { return r.json(); })
			.then(function(saveData) {
				if (saveData.status !== 'ok') {
					throw new Error(saveData.error || 'Save failed');
				}
				return fetch(OC.generateUrl('/apps/nldesign/settings/tokenset'), {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken },
					body: JSON.stringify({ tokenSet: newTokenSetId })
				});
			})
			.then(function(r) { return r.json(); })
			.then(function(tsData) {
				overlay.remove();
				if (tsData.status === 'ok' && tokenSetSelect !== null) {
					tokenSetSelect.dataset.previousValue = newTokenSetId;
				}
				OC.Notification.showTemporary(t('nldesign', 'Token overrides applied.'));
				initTokenEditor();
			})
			.catch(function(err) {
				btn.disabled    = false;
				btn.textContent = t('nldesign', 'Apply selected');
				console.error('Error applying token set:', err);
				OC.Notification.showTemporary(t('nldesign', 'Failed to apply token set.'));
			});
		});
	}

	/* ==========================================================================
	 * HELPERS
	 * ========================================================================== */

	function normaliseColorForPicker(value) {
		if (value === undefined || value === null || value === '') {
			return '#000000';
		}
		var v = value.trim();
		if (/^#[0-9a-fA-F]{6}$/.test(v) === true) {
			return v;
		}
		if (/^#[0-9a-fA-F]{3}$/.test(v) === true) {
			return '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
		}
		try {
			var canvas  = document.createElement('canvas');
			canvas.width = canvas.height = 1;
			var ctx = canvas.getContext('2d');
			ctx.fillStyle = v;
			ctx.fillRect(0, 0, 1, 1);
			var d = ctx.getImageData(0, 0, 1, 1).data;
			return '#' + ('0' + d[0].toString(16)).slice(-2) + ('0' + d[1].toString(16)).slice(-2) + ('0' + d[2].toString(16)).slice(-2);
		} catch (e) {
			return '#000000';
		}
	}

	// Initialise token editor on page load.
	initTokenEditor();

}
})();
