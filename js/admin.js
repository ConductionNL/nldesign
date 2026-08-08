/**
 * NL Design profile administration.
 */

document.addEventListener('DOMContentLoaded', () => {
	const root = document.getElementById('nldesign-settings');
	const profileSelect = document.getElementById('nldesign-token-set-select');
	if (!root || !profileSelect) {
		return;
	}

	const rollbackButton = document.getElementById('nldesign-rollback-profile');
	const revisionHint = document.getElementById('nldesign-profile-revision');
	const statusNode = document.getElementById('nldesign-status');
	const previewBox = document.querySelector('.nldesign-preview-box');
	const planMessage = document.getElementById('nldesign-theming-plan-message');
	const planSteps = document.getElementById('nldesign-theming-plan-steps');
	const historyList = document.getElementById('nldesign-profile-history-list');
	const NATIVE_SELECTION = '__native__';
	const UNAVAILABLE_SELECTION = '__unavailable__';

	let profiles = [];
	try {
		profiles = JSON.parse(root.dataset.tokenSets || '[]');
		if (!Array.isArray(profiles)) {
			profiles = [];
		}
	} catch (error) {
		console.error('Could not parse NL Design profile metadata.', error);
	}

	const profileIndex = new Map(
		profiles
			.filter((profile) => profile && typeof profile.id === 'string')
			.map((profile) => [profile.id, profile])
	);
	let activeProfile = root.dataset.currentTokenSet || '';
	let profileRevision = root.dataset.profileRevision || '';
	let previousProfile = root.dataset.previousProfile || '';
	let canRollback = root.dataset.canRollback === '1';
	let busy = false;
	let planRequestSequence = 0;
	let historyRequestSequence = 0;

	/**
	 * Send a same-origin JSON request and reject non-success responses.
	 *
	 * @param {string} path Relative app path.
	 * @param {object} options Request options.
	 * @return {Promise<object>} Parsed response body.
	 */
	async function requestJson(path, options = {}) {
		const method = options.method || 'GET';
		const headers = { Accept: 'application/json' };
		const request = {
			method,
			headers,
			credentials: 'same-origin',
		};

		if (method !== 'GET') {
			headers['Content-Type'] = 'application/json';
			headers.requesttoken = OC.requestToken;
			request.body = JSON.stringify(options.body || {});
		}

		const response = await fetch(OC.generateUrl(`/apps/nldesign${path}`), request);
		let payload = {};
		try {
			payload = await response.json();
		} catch (error) {
			if (response.ok) {
				console.error('Could not parse the NL Design response.', error);
				throw new Error('The server returned an invalid JSON response.');
			}
		}

		if (!response.ok) {
			const requestError = new Error(payload.error || `Request failed (${response.status}).`);
			requestError.status = response.status;
			requestError.payload = payload;
			throw requestError;
		}

		return payload;
	}

	function setBusy(nextBusy) {
		busy = nextBusy;
		profileSelect.disabled = nextBusy;
		updateRollbackAvailability();
	}

	function setStatus(message) {
		if (statusNode) {
			statusNode.textContent = message;
		}
	}

	function notify(message) {
		setStatus(message);
		OC.Notification.showTemporary(message);
	}

	function assertProfileStatePayload(data) {
		const hasTokenSet = data && (data.tokenSet === null || typeof data.tokenSet === 'string');
		const hasRevision = data
			&& typeof data.revision === 'string'
			&& /^[a-f0-9]{20}$/.test(data.revision);
		const hasPreviousProfile = data
			&& (data.previousProfile === null || typeof data.previousProfile === 'string');
		if (!data
			|| data.status !== 'ok'
			|| !hasTokenSet
			|| !hasRevision
			|| !hasPreviousProfile
			|| typeof data.canRollback !== 'boolean'
		) {
			throw new Error('The server returned an invalid profile-state response.');
		}
	}

	function applyState(data) {
		if (data.tokenSet === null) {
			activeProfile = '';
		} else if (typeof data.tokenSet === 'string') {
			activeProfile = data.tokenSet;
		}
		profileSelect.value = getSelectionForActiveProfile();
		profileRevision = typeof data.revision === 'string' ? data.revision : '';
		previousProfile = typeof data.previousProfile === 'string' ? data.previousProfile : '';
		canRollback = typeof data.canRollback === 'boolean' ? data.canRollback : canRollback;

		root.dataset.currentTokenSet = activeProfile;
		root.dataset.profileRevision = profileRevision;
		root.dataset.previousProfile = previousProfile;
		root.dataset.canRollback = canRollback ? '1' : '0';
		updateRevisionHint();
		updateRollbackAvailability();
		updatePreview(profileIndex.has(activeProfile) ? activeProfile : '');
	}

	function getSelectionForActiveProfile() {
		if (activeProfile === '') {
			return NATIVE_SELECTION;
		}
		return profileIndex.has(activeProfile) ? activeProfile : UNAVAILABLE_SELECTION;
	}

	function updateRevisionHint() {
		if (revisionHint) {
			revisionHint.textContent = t(
				'nldesign',
				'Active revision: %s',
				[profileRevision || 'N/A']
			);
		}
	}

	function updateRollbackAvailability() {
		if (rollbackButton) {
			rollbackButton.disabled = busy
				|| !canRollback
				|| (previousProfile !== '' && !profileIndex.has(previousProfile));
		}
	}

	function getPreviewColor(profileId) {
		const metadata = profileIndex.get(profileId);
		const color = metadata && metadata.theming ? metadata.theming.primary_color : '';
		if (typeof color === 'string' && /^#[0-9a-f]{6}$/i.test(color)) {
			return color;
		}

		const fallback = getComputedStyle(document.documentElement)
			.getPropertyValue('--color-primary-element')
			.trim();
		return /^#[0-9a-f]{6}$/i.test(fallback) ? fallback : '#808080';
	}

	function getPreviewTextColor(background) {
		const channels = [1, 3, 5]
			.map((offset) => Number.parseInt(background.slice(offset, offset + 2), 16) / 255)
			.map((channel) => channel <= 0.04045
				? channel / 12.92
				: ((channel + 0.055) / 1.055) ** 2.4);
		const luminance = (0.2126 * channels[0])
			+ (0.7152 * channels[1])
			+ (0.0722 * channels[2]);

		return luminance > 0.179 ? '#000000' : '#ffffff';
	}

	function updatePreview(profileId) {
		if (previewBox) {
			const previewColor = getPreviewColor(profileId);
			previewBox.style.setProperty('--nldesign-preview-primary', previewColor);
			previewBox.style.setProperty(
				'--nldesign-preview-primary-text',
				getPreviewTextColor(previewColor)
			);
		}
	}

	async function syncActiveState() {
		const data = await requestJson('/settings/tokenset');
		assertProfileStatePayload(data);
		applyState(data);
		await refreshThemingPlan(activeProfile);
	}

	async function saveProfile(selection) {
		const useNativePresentation = selection === NATIVE_SELECTION;
		if (busy || (!useNativePresentation && !profileIndex.has(selection))) {
			return;
		}

		setBusy(true);
		updatePreview(useNativePresentation ? '' : selection);
		setStatus(t('nldesign', 'Saving profile…'));
		try {
			const data = await requestJson(
				useNativePresentation ? '/settings/deactivate' : '/settings/tokenset',
				{
				method: 'POST',
				body: useNativePresentation
					? { expectedRevision: profileRevision }
					: { tokenSet: selection, expectedRevision: profileRevision },
				}
			);
			assertProfileStatePayload(data);
			applyState(data);
			notify(useNativePresentation
				? t('nldesign', 'NL Design profile deactivated. Reload open pages to use native Nextcloud presentation.')
				: t('nldesign', 'Profile updated. Reload open pages to apply it everywhere.'));
			await Promise.all([refreshThemingPlan(activeProfile), refreshHistory()]);
		} catch (error) {
			console.error('Could not save NL Design profile.', error);
			profileSelect.value = getSelectionForActiveProfile();
			updatePreview(activeProfile);
			if (error.payload && error.payload.status === 'revision_mismatch') {
				notify(t('nldesign', 'The profile changed in another session. The current state was reloaded.'));
				try {
					await syncActiveState();
				} catch (syncError) {
					console.error('Could not reload NL Design profile state.', syncError);
				}
			} else {
				notify(t('nldesign', 'Could not update the profile.'));
			}
		} finally {
			setBusy(false);
		}
	}

	async function rollbackProfile() {
		if (busy
			|| !canRollback
			|| (previousProfile !== '' && !profileIndex.has(previousProfile))
		) {
			return;
		}

		setBusy(true);
		setStatus(t('nldesign', 'Rolling back profile…'));
		try {
			const data = await requestJson('/settings/rollback', {
				method: 'POST',
				body: { expectedRevision: profileRevision },
			});
			assertProfileStatePayload(data);
			applyState(data);
			notify(t('nldesign', 'Profile rolled back.'));
			await Promise.all([refreshThemingPlan(activeProfile), refreshHistory()]);
		} catch (error) {
			console.error('Could not roll back NL Design profile.', error);
			notify(t('nldesign', 'Could not roll back the profile.'));
			if (error.payload && error.payload.status === 'revision_mismatch') {
				try {
					await syncActiveState();
				} catch (syncError) {
					console.error('Could not reload NL Design profile state.', syncError);
				}
			}
		} finally {
			setBusy(false);
		}
	}

	function renderListMessage(list, message) {
		if (!list) {
			return;
		}

		list.textContent = '';
		const item = document.createElement('li');
		item.textContent = message;
		list.appendChild(item);
	}

	async function refreshThemingPlan(profileId) {
		const requestSequence = ++planRequestSequence;
		if (!planMessage || !planSteps) {
			return;
		}
		if (!profileIndex.has(profileId)) {
			planMessage.textContent = profileId === ''
				? t('nldesign', 'Native Nextcloud presentation is active; no profile recommendations apply.')
				: t(
					'nldesign',
					'The stored profile is unavailable. Select a replacement before using Theming recommendations.'
				);
			planSteps.textContent = '';
			return;
		}

		planMessage.textContent = t('nldesign', 'Loading manual recommendations…');
		planSteps.textContent = '';
		try {
			const data = await requestJson(
				`/settings/theming-plan?tokenSet=${encodeURIComponent(profileId)}`
			);
			if (requestSequence !== planRequestSequence) {
				return;
			}
			const plan = data.plan || {};
			const steps = Array.isArray(plan.steps) ? plan.steps : [];
			planMessage.textContent = plan.note || t(
				'nldesign',
				'These values must be applied manually in Nextcloud Theming.'
			);

			if (steps.length === 0) {
				renderListMessage(
					planSteps,
					t('nldesign', 'This profile has no Nextcloud Theming recommendations.')
				);
				return;
			}

			for (const step of steps) {
				const item = document.createElement('li');
				const label = document.createElement('strong');
				const value = document.createElement('code');
				const instruction = document.createElement('span');
				label.textContent = step.field || t('nldesign', 'Field');
				value.textContent = step.value || '';
				instruction.textContent = step.manual_instruction || '';
				item.append(label, value, instruction);
				planSteps.appendChild(item);
			}
		} catch (error) {
			if (requestSequence !== planRequestSequence) {
				return;
			}
			console.error('Could not load NL Design theming recommendations.', error);
			planMessage.textContent = t('nldesign', 'Could not load manual recommendations.');
		}
	}

	async function refreshHistory() {
		const requestSequence = ++historyRequestSequence;
		if (!historyList) {
			return;
		}

		try {
			const data = await requestJson('/settings/profile-history');
			if (requestSequence !== historyRequestSequence) {
				return;
			}
			const entries = Array.isArray(data.history) ? data.history : [];
			if (entries.length === 0) {
				renderListMessage(
					historyList,
					t('nldesign', 'No profile changes have been recorded yet.')
				);
				return;
			}

			historyList.textContent = '';
			for (const entry of entries) {
				const item = document.createElement('li');
				item.textContent = t(
					'nldesign',
					'%s changed %s → %s at %s',
					[
						typeof entry.actor === 'string' ? entry.actor : t('nldesign', 'admin'),
						entry.from_profile_id || t('nldesign', 'none'),
						entry.to_profile_id || t('nldesign', 'none'),
						entry.timestamp || t('nldesign', 'unknown time'),
					]
				);
				historyList.appendChild(item);
			}
		} catch (error) {
			if (requestSequence !== historyRequestSequence) {
				return;
			}
			console.error('Could not load NL Design profile history.', error);
			renderListMessage(historyList, t('nldesign', 'Could not load profile history.'));
		}
	}

	profileSelect.addEventListener('change', () => {
		void saveProfile(profileSelect.value);
	});
	if (rollbackButton) {
		rollbackButton.addEventListener('click', () => {
			void rollbackProfile();
		});
	}

	applyState({
		tokenSet: activeProfile === '' ? null : activeProfile,
		revision: profileRevision,
		previousProfile,
		canRollback,
	});
	if (profiles.length === 0) {
		setStatus(t('nldesign', 'No selectable profiles were found; native Nextcloud remains available.'));
		if (planMessage) {
			planMessage.textContent = t('nldesign', 'No profile is available.');
		}
	} else {
		void refreshThemingPlan(activeProfile);
	}
	void refreshHistory();
});
