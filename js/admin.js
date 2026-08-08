/**
 * NL Design versioned profile library administration.
 */

document.addEventListener('DOMContentLoaded', () => {
	const root = document.getElementById('nldesign-settings');
	const profileGrid = document.getElementById('nldesign-profile-grid');
	if (!root || !profileGrid) {
		return;
	}

	const statusNode = document.getElementById('nldesign-status');
	const activeHeading = document.getElementById('nldesign-active-heading');
	const activeDescription = document.getElementById('nldesign-active-description');
	const activeBadges = document.getElementById('nldesign-active-badges');
	const previewBox = document.querySelector('.nldesign-preview-box');
	const rollbackButton = document.getElementById('nldesign-rollback-profile');
	const deactivateButton = document.getElementById('nldesign-deactivate-profile');
	const revisionHint = document.getElementById('nldesign-profile-revision');
	const searchInput = document.getElementById('nldesign-profile-search');
	const libraryEmpty = document.getElementById('nldesign-library-empty');
	const planMessage = document.getElementById('nldesign-theming-plan-message');
	const planSteps = document.getElementById('nldesign-theming-plan-steps');
	const historyList = document.getElementById('nldesign-profile-history-list');
	const installerDialog = document.getElementById('nldesign-installer');
	const openInstallerButton = document.getElementById('nldesign-open-installer');
	const closeInstallerButton = document.getElementById('nldesign-close-installer');
	const cancelInstallerButton = document.getElementById('nldesign-cancel-installer');
	const installButton = document.getElementById('nldesign-install-profile');
	const fileInput = document.getElementById('nldesign-profile-pack-file');
	const fileName = document.getElementById('nldesign-file-name');
	const installSummary = document.getElementById('nldesign-install-summary');
	const installError = document.getElementById('nldesign-install-error');
	const modeButtons = Array.from(document.querySelectorAll('[data-preview-mode]'));
	const MAX_PACK_BYTES = 65536;
	const PROFILE_ID_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
	const VERSION_PATTERN = /^(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+)(?:\.(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+))*)?$/;
	const COLOR_PATTERN = /^#[0-9a-f]{6}$/i;
	const runtimeSupported = root.dataset.runtimeSupported === '1';

	let profiles = parseInitialProfiles();
	let profileIndex = buildProfileIndex(profiles);
	let activeProfile = root.dataset.currentTokenSet || '';
	let activeVersion = root.dataset.currentProfileVersion || '';
	let profileRevision = root.dataset.profileRevision || '';
	let previousProfile = root.dataset.previousProfile || '';
	let previousVersion = root.dataset.previousProfileVersion || '';
	let canRollback = root.dataset.canRollback === '1';
	let previewMode = 'light';
	let pendingPack = null;
	let busy = false;
	let planRequestSequence = 0;
	let historyRequestSequence = 0;

	/**
	 * Parse the server-normalized initial catalogue.
	 *
	 * @return {Array<object>} Profiles.
	 */
	function parseInitialProfiles() {
		try {
			const parsed = JSON.parse(root.dataset.tokenSets || '[]');
			return Array.isArray(parsed) ? normalizeProfiles(parsed) : [];
		} catch (error) {
			console.error('Could not parse NL Design profile metadata.', error);
			return [];
		}
	}

	/**
	 * Retain only records with a safe immutable identity.
	 *
	 * @param {Array<object>} values Candidate records.
	 * @return {Array<object>} Safe records.
	 */
	function normalizeProfiles(values) {
		const seen = new Set();
		return values.filter((profile) => {
			if (!profile
				|| typeof profile.id !== 'string'
				|| typeof profile.version !== 'string'
				|| !PROFILE_ID_PATTERN.test(profile.id)
				|| !VERSION_PATTERN.test(profile.version)
			) {
				return false;
			}

			const key = profileKey(profile.id, profile.version);
			if (seen.has(key)) {
				return false;
			}
			seen.add(key);
			return true;
		});
	}

	function profileKey(profileId, profileVersion) {
		return `${profileId}@${profileVersion}`;
	}

	function buildProfileIndex(values) {
		return new Map(values.map((profile) => [profileKey(profile.id, profile.version), profile]));
	}

	function setProfiles(values) {
		profiles = normalizeProfiles(Array.isArray(values) ? values : []);
		profileIndex = buildProfileIndex(profiles);
		renderInterface();
	}

	/**
	 * Read the current browser capability instead of branching on a Nextcloud
	 * version string.
	 *
	 * @return {string} Current CSRF token.
	 */
	function getNextcloudRequestToken() {
		const token = window.OC?.requestToken;
		if (typeof token !== 'string' || token === '') {
			throw new Error('The Nextcloud request-token capability is unavailable.');
		}
		return token;
	}

	/**
	 * Resolve an app URL through Nextcloud's current routing capability.
	 *
	 * @param {string} path App-relative path.
	 * @return {string} Nextcloud URL.
	 */
	function generateNextcloudUrl(path) {
		if (typeof window.OC?.generateUrl !== 'function') {
			throw new Error('The Nextcloud URL-generation capability is unavailable.');
		}
		return window.OC.generateUrl(path);
	}

	/**
	 * Send a same-origin JSON request and reject non-success responses.
	 *
	 * @param {string} path Relative app path.
	 * @param {object} options Request options.
	 * @return {Promise<object>} Parsed response body.
	 */
	async function requestJson(path, options = {}) {
		const method = options.method || 'GET';
		const headers = {
			Accept: 'application/json',
			requesttoken: getNextcloudRequestToken(),
		};
		const request = {
			method,
			headers,
			credentials: 'same-origin',
		};

		if (method !== 'GET') {
			headers['Content-Type'] = 'application/json';
			request.body = JSON.stringify(options.body || {});
		}

		const response = await fetch(generateNextcloudUrl(`/apps/nldesign${path}`), request);
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

	function setStatus(message) {
		if (statusNode) {
			statusNode.textContent = message;
		}
	}

	function notify(message) {
		setStatus(message);
		if (window.OCP?.Toast && typeof window.OCP.Toast.info === 'function') {
			window.OCP.Toast.info(message);
			return;
		}
		if (typeof window.OC?.Notification?.showTemporary === 'function') {
			window.OC.Notification.showTemporary(message);
		}
	}

	function setBusy(nextBusy) {
		busy = nextBusy;
		root.classList.toggle('nldesign-is-busy', busy);
		if (busy) {
			root.querySelectorAll('button, select').forEach((control) => {
				control.disabled = true;
			});
			if (fileInput) {
				fileInput.disabled = true;
			}
		} else {
			root.querySelectorAll('button, select').forEach((control) => {
				control.disabled = false;
			});
			if (fileInput) {
				fileInput.disabled = false;
			}
			renderInterface();
			updateInstallerButton();
		}
	}

	function getActiveMetadata() {
		return activeProfile && activeVersion
			? profileIndex.get(profileKey(activeProfile, activeVersion)) || null
			: null;
	}

	function createBadge(label, variant = '') {
		const badge = document.createElement('span');
		badge.className = `nldesign-badge${variant ? ` ${variant}` : ''}`;
		badge.textContent = label;
		return badge;
	}

	function getFallbackPrimary() {
		const fallback = getComputedStyle(document.documentElement)
			.getPropertyValue('--color-primary-element')
			.trim();
		return COLOR_PATTERN.test(fallback) ? fallback : '#00679e';
	}

	function getAccessibleTextColor(background) {
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

	function getProfileMode(profile, mode) {
		const preview = profile && profile.preview && typeof profile.preview === 'object'
			? profile.preview
			: {};
		const candidate = preview[mode] || preview.light;
		if (candidate
			&& COLOR_PATTERN.test(candidate.primary || '')
			&& COLOR_PATTERN.test(candidate.primary_text || '')
			&& COLOR_PATTERN.test(candidate.primary_hover || '')
		) {
			return candidate;
		}

		const hinted = profile && profile.theming ? profile.theming.primary_color : '';
		const primary = COLOR_PATTERN.test(hinted || '') ? hinted : getFallbackPrimary();
		return {
			primary,
			primary_text: getAccessibleTextColor(primary),
			primary_hover: primary,
		};
	}

	function updatePreview(profile) {
		if (!previewBox) {
			return;
		}

		const mode = getProfileMode(profile, previewMode);
		previewBox.style.setProperty('--nldesign-preview-primary', mode.primary);
		previewBox.style.setProperty('--nldesign-preview-primary-text', mode.primary_text);
		previewBox.style.setProperty('--nldesign-preview-primary-hover', mode.primary_hover);
		previewBox.dataset.previewCurrentMode = previewMode;
		previewBox.classList.toggle('is-dark', previewMode === 'dark');
		modeButtons.forEach((button) => {
			const active = button.dataset.previewMode === previewMode;
			button.classList.toggle('active', active);
			button.setAttribute('aria-pressed', active ? 'true' : 'false');
		});
	}

	function isPreviousVersionAvailable() {
		if (!canRollback) {
			return false;
		}
		if (previousProfile === '') {
			return true;
		}
		return profileIndex.has(profileKey(previousProfile, previousVersion));
	}

	function renderActiveProfile() {
		const metadata = getActiveMetadata();
		if (activeBadges) {
			activeBadges.textContent = '';
		}

		if (activeProfile === '') {
			if (activeHeading) {
				activeHeading.textContent = t('nldesign', 'Native Nextcloud');
			}
			if (activeDescription) {
				activeDescription.textContent = t(
					'nldesign',
					'No NL Design profile is active. Nextcloud controls the current presentation.'
				);
			}
			activeBadges?.appendChild(createBadge(t('nldesign', 'Native'), 'is-native'));
			if (deactivateButton) {
				deactivateButton.hidden = true;
			}
			updatePreview(null);
		} else if (metadata) {
			if (activeHeading) {
				activeHeading.textContent = metadata.name || metadata.id;
			}
			if (activeDescription) {
				activeDescription.textContent = metadata.description || '';
			}
			activeBadges?.append(
				createBadge(`v${metadata.version}`, 'is-version'),
				createBadge(
					metadata.origin === 'installed' ? t('nldesign', 'Installed') : t('nldesign', 'Built-in'),
					metadata.origin === 'installed' ? 'is-installed' : 'is-built-in'
				)
			);
			if (deactivateButton) {
				deactivateButton.hidden = false;
			}
			updatePreview(metadata);
		} else {
			if (activeHeading) {
				activeHeading.textContent = t('nldesign', 'Unavailable profile');
			}
			if (activeDescription) {
				activeDescription.textContent = t(
					'nldesign',
					'The active reference {profile} v{version} is unavailable. Choose a replacement or return to native Nextcloud.',
					{ profile: activeProfile, version: activeVersion || '?' }
				);
			}
			activeBadges?.appendChild(createBadge(t('nldesign', 'Unavailable'), 'is-warning'));
			if (deactivateButton) {
				deactivateButton.hidden = false;
			}
			updatePreview(null);
		}

		if (rollbackButton) {
			rollbackButton.disabled = busy || !isPreviousVersionAvailable();
		}
		if (deactivateButton) {
			deactivateButton.disabled = busy || activeProfile === '';
		}
		if (revisionHint) {
			revisionHint.textContent = t(
				'nldesign',
				'Revision {revision}',
				{ revision: profileRevision || 'N/A' }
			);
		}
	}

	function groupProfiles() {
		const groups = new Map();
		profiles.forEach((profile) => {
			if (!groups.has(profile.id)) {
				groups.set(profile.id, []);
			}
			groups.get(profile.id).push(profile);
		});

		return Array.from(groups.values())
			.sort((left, right) => String(left[0].name || left[0].id)
				.localeCompare(String(right[0].name || right[0].id)));
	}

	function createNativeCard() {
		const card = document.createElement('article');
		card.className = `nldesign-profile-card is-native${activeProfile === '' ? ' is-active' : ''}`;

		const visual = document.createElement('div');
		visual.className = 'nldesign-card-visual is-native';
		const title = document.createElement('h4');
		title.textContent = t('nldesign', 'Native Nextcloud');
		const description = document.createElement('p');
		description.textContent = t('nldesign', 'Use Nextcloud without an NL Design profile projection.');
		const badges = document.createElement('div');
		badges.className = 'nldesign-badges';
		badges.appendChild(createBadge(t('nldesign', 'Native'), 'is-native'));
		const actions = document.createElement('div');
		actions.className = 'nldesign-card-actions';
		const activate = document.createElement('button');
		activate.type = 'button';
		activate.className = activeProfile === '' ? 'button' : 'button primary';
		activate.dataset.nldesignAction = 'activate-native';
		activate.disabled = busy || activeProfile === '';
		activate.textContent = activeProfile === '' ? t('nldesign', 'Active') : t('nldesign', 'Activate');
		activate.addEventListener('click', () => void saveProfile(null, null));
		actions.appendChild(activate);
		card.append(visual, title, description, badges, actions);
		return card;
	}

	function isRollbackTarget(profile) {
		return canRollback
			&& previousProfile === profile.id
			&& (previousVersion === '' || previousVersion === profile.version);
	}

	function createProfileCard(versions) {
		const activeInGroup = activeProfile === versions[0].id
			? versions.find((profile) => profile.version === activeVersion)
			: null;
		let selected = activeInGroup || versions[0];
		const card = document.createElement('article');
		card.className = `nldesign-profile-card${activeInGroup ? ' is-active' : ''}`;

		const visual = document.createElement('div');
		visual.className = 'nldesign-card-visual';
		const lightSwatch = document.createElement('span');
		lightSwatch.className = 'nldesign-card-swatch';
		const darkSwatch = document.createElement('span');
		darkSwatch.className = 'nldesign-card-swatch is-dark';
		visual.append(lightSwatch, darkSwatch);

		const headingRow = document.createElement('div');
		headingRow.className = 'nldesign-card-heading';
		const title = document.createElement('h4');
		const activeBadge = createBadge(t('nldesign', 'Active'), 'is-active');
		headingRow.append(title, activeBadge);

		const description = document.createElement('p');
		description.className = 'nldesign-card-description';
		const versionLabel = document.createElement('label');
		versionLabel.className = 'nldesign-version-select';
		const labelText = document.createElement('span');
		labelText.textContent = t('nldesign', 'Version');
		const select = document.createElement('select');
		select.className = 'nldesign-profile-version-select';
		select.setAttribute('aria-label', t('nldesign', 'Profile version'));
		versions.forEach((profile) => {
			const option = document.createElement('option');
			option.value = profile.version;
			option.textContent = `v${profile.version} · ${profile.origin === 'installed'
				? t('nldesign', 'Installed')
				: t('nldesign', 'Built-in')}`;
			option.selected = profile.version === selected.version;
			select.appendChild(option);
		});
		versionLabel.append(labelText, select);

		const badges = document.createElement('div');
		badges.className = 'nldesign-badges';
		const metadata = document.createElement('p');
		metadata.className = 'nldesign-card-meta';
		const actions = document.createElement('div');
		actions.className = 'nldesign-card-actions';
		const activate = document.createElement('button');
		activate.type = 'button';
		activate.dataset.nldesignAction = 'activate';
		const uninstall = document.createElement('button');
		uninstall.type = 'button';
		uninstall.className = 'button nldesign-danger-button';
		uninstall.dataset.nldesignAction = 'uninstall';
		uninstall.textContent = t('nldesign', 'Uninstall');
		actions.append(activate, uninstall);

		function updateSelectedVersion() {
			selected = versions.find((profile) => profile.version === select.value) || versions[0];
			const light = getProfileMode(selected, 'light');
			const dark = getProfileMode(selected, 'dark');
			lightSwatch.style.backgroundColor = light.primary;
			darkSwatch.style.backgroundColor = dark.primary;
			title.textContent = selected.name || selected.id;
			description.textContent = selected.description || '';
			activeBadge.hidden = activeProfile !== selected.id || activeVersion !== selected.version;
			badges.textContent = '';
			badges.append(
				createBadge(
					selected.origin === 'installed' ? t('nldesign', 'Installed') : t('nldesign', 'Built-in'),
					selected.origin === 'installed' ? 'is-installed' : 'is-built-in'
				),
				createBadge(selected.projection || 'nextcloud-core-v1', 'is-projection')
			);
			if (versions.length > 1) {
				badges.appendChild(createBadge(
					t('nldesign', 'Versions: {count}', { count: String(versions.length) }),
					'is-version'
				));
			}
			const provenance = [selected.publisher, selected.license]
				.filter((value) => typeof value === 'string' && value !== '');
			metadata.textContent = provenance.length > 0
				? provenance.join(' · ')
				: t('nldesign', 'Packaged with this app');

			const isActive = activeProfile === selected.id && activeVersion === selected.version;
			activate.className = isActive ? 'button' : 'button primary';
			activate.disabled = busy || isActive || !runtimeSupported;
			activate.textContent = isActive ? t('nldesign', 'Active') : t('nldesign', 'Activate');
			activate.title = runtimeSupported
				? ''
				: t('nldesign', 'This Nextcloud version has no verified profile projection adapter.');
			uninstall.hidden = selected.origin !== 'installed';
			uninstall.disabled = busy || isActive || isRollbackTarget(selected);
			uninstall.title = isActive
				? t('nldesign', 'An active version cannot be uninstalled.')
				: isRollbackTarget(selected)
					? t('nldesign', 'This version is retained for rollback.')
					: '';
		}

		select.addEventListener('change', updateSelectedVersion);
		activate.addEventListener('click', () => void saveProfile(selected.id, selected.version));
		uninstall.addEventListener('click', () => void uninstallProfile(selected));
		updateSelectedVersion();
		card.append(visual, headingRow, description, versionLabel, badges, metadata, actions);
		return card;
	}

	function renderLibrary() {
		profileGrid.textContent = '';
		const query = searchInput ? searchInput.value.trim().toLocaleLowerCase() : '';
		let visibleCards = 0;
		const nativeSearch = t('nldesign', 'Native Nextcloud').toLocaleLowerCase();
		if (query === '' || nativeSearch.includes(query)) {
			profileGrid.appendChild(createNativeCard());
			visibleCards += 1;
		}

		groupProfiles().forEach((versions) => {
			const searchable = versions
				.flatMap((profile) => [
					profile.name,
					profile.description,
					profile.id,
					profile.publisher,
				])
				.filter((value) => typeof value === 'string')
				.join(' ')
				.toLocaleLowerCase();
			if (query !== '' && !searchable.includes(query)) {
				return;
			}

			profileGrid.appendChild(createProfileCard(versions));
			visibleCards += 1;
		});

		if (libraryEmpty) {
			libraryEmpty.hidden = visibleCards > 0;
		}
	}

	function renderInterface() {
		renderActiveProfile();
		renderLibrary();
		if (openInstallerButton) {
			openInstallerButton.disabled = busy;
		}
	}

	function assertProfileStatePayload(data) {
		const hasTokenSet = data && (data.tokenSet === null || typeof data.tokenSet === 'string');
		const hasVersion = data && (data.profileVersion === null || typeof data.profileVersion === 'string');
		const hasRevision = data
			&& typeof data.revision === 'string'
			&& /^[a-f0-9]{20}$/.test(data.revision);
		const hasPreviousProfile = data
			&& (data.previousProfile === null || typeof data.previousProfile === 'string');
		const hasPreviousVersion = data
			&& (data.previousProfileVersion === null || typeof data.previousProfileVersion === 'string');
		if (!data
			|| data.status !== 'ok'
			|| !hasTokenSet
			|| !hasVersion
			|| !hasRevision
			|| !hasPreviousProfile
			|| !hasPreviousVersion
			|| typeof data.canRollback !== 'boolean'
			|| ((data.tokenSet === null) !== (data.profileVersion === null))
		) {
			throw new Error('The server returned an invalid profile-state response.');
		}
	}

	function applyState(data) {
		activeProfile = typeof data.tokenSet === 'string' ? data.tokenSet : '';
		activeVersion = typeof data.profileVersion === 'string' ? data.profileVersion : '';
		profileRevision = typeof data.revision === 'string' ? data.revision : '';
		previousProfile = typeof data.previousProfile === 'string' ? data.previousProfile : '';
		previousVersion = typeof data.previousProfileVersion === 'string'
			? data.previousProfileVersion
			: '';
		canRollback = data.canRollback;

		root.dataset.currentTokenSet = activeProfile;
		root.dataset.currentProfileVersion = activeVersion;
		root.dataset.profileRevision = profileRevision;
		root.dataset.previousProfile = previousProfile;
		root.dataset.previousProfileVersion = previousVersion;
		root.dataset.canRollback = canRollback ? '1' : '0';
		renderInterface();
	}

	async function syncActiveState() {
		const data = await requestJson('/settings/tokenset');
		assertProfileStatePayload(data);
		applyState(data);
		await refreshThemingPlan(activeProfile, activeVersion);
	}

	async function saveProfile(profileId, profileVersion) {
		if (busy) {
			return;
		}
		if (profileId !== null && !runtimeSupported) {
			notify(t('nldesign', 'This Nextcloud version has no verified profile projection adapter.'));
			return;
		}
		if (profileId !== null && !profileIndex.has(profileKey(profileId, profileVersion))) {
			return;
		}

		setBusy(true);
		setStatus(profileId === null
			? t('nldesign', 'Returning to native Nextcloud…')
			: t('nldesign', 'Activating {profile} v{version}…', {
				profile: profileId,
				version: profileVersion,
			}));
		try {
			const data = await requestJson(
				profileId === null ? '/settings/deactivate' : '/settings/tokenset',
				{
					method: 'POST',
					body: profileId === null
						? { expectedRevision: profileRevision }
						: {
							tokenSet: profileId,
							profileVersion,
							expectedRevision: profileRevision,
						},
				}
			);
			assertProfileStatePayload(data);
			applyState(data);
			notify(profileId === null
				? t('nldesign', 'Native Nextcloud is active. Reload open pages to apply it everywhere.')
				: t('nldesign', 'Profile version activated. Reload open pages to apply it everywhere.'));
			await Promise.all([
				refreshThemingPlan(activeProfile, activeVersion),
				refreshHistory(),
			]);
		} catch (error) {
			console.error('Could not change the active NL Design profile.', error);
			if (error.payload && error.payload.status === 'revision_mismatch') {
				notify(t('nldesign', 'The profile changed in another session. The current state was reloaded.'));
				try {
					await syncActiveState();
				} catch (syncError) {
					console.error('Could not reload NL Design profile state.', syncError);
				}
			} else {
				notify(t('nldesign', 'Could not change the active profile.'));
			}
		} finally {
			setBusy(false);
		}
	}

	async function rollbackProfile() {
		if (busy || !isPreviousVersionAvailable()) {
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
			await Promise.all([
				refreshThemingPlan(activeProfile, activeVersion),
				refreshHistory(),
			]);
		} catch (error) {
			console.error('Could not roll back the NL Design profile.', error);
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

	async function refreshThemingPlan(profileId, profileVersion) {
		const requestSequence = ++planRequestSequence;
		if (!planMessage || !planSteps) {
			return;
		}
		if (!profileIndex.has(profileKey(profileId, profileVersion))) {
			planMessage.textContent = profileId === ''
				? t('nldesign', 'Native Nextcloud presentation is active; no profile recommendations apply.')
				: t('nldesign', 'The active profile version is unavailable.');
			planSteps.textContent = '';
			return;
		}

		planMessage.textContent = t('nldesign', 'Loading manual recommendations…');
		planSteps.textContent = '';
		try {
			const data = await requestJson(
				`/settings/theming-plan?tokenSet=${encodeURIComponent(profileId)}`
				+ `&profileVersion=${encodeURIComponent(profileVersion)}`
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

			steps.forEach((step) => {
				const item = document.createElement('li');
				const label = document.createElement('strong');
				const value = document.createElement('code');
				const instruction = document.createElement('span');
				label.textContent = step.field || t('nldesign', 'Field');
				value.textContent = step.value || '';
				instruction.textContent = step.manual_instruction || '';
				item.append(label, value, instruction);
				planSteps.appendChild(item);
			});
		} catch (error) {
			if (requestSequence !== planRequestSequence) {
				return;
			}
			console.error('Could not load NL Design Theming recommendations.', error);
			planMessage.textContent = t('nldesign', 'Could not load manual recommendations.');
		}
	}

	function formatProfileReference(profileId, profileVersion) {
		if (typeof profileId !== 'string' || profileId === '') {
			return t('nldesign', 'none');
		}
		return typeof profileVersion === 'string' && profileVersion !== ''
			? `${profileId} v${profileVersion}`
			: profileId;
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
				renderListMessage(historyList, t('nldesign', 'No profile changes have been recorded yet.'));
				return;
			}

			historyList.textContent = '';
			entries.forEach((entry) => {
				const item = document.createElement('li');
				item.textContent = t(
					'nldesign',
					'{actor} changed {from} → {to} at {time}',
					{
						actor: typeof entry.actor === 'string' ? entry.actor : t('nldesign', 'admin'),
						from: formatProfileReference(entry.from_profile_id, entry.from_profile_version),
						to: formatProfileReference(entry.to_profile_id, entry.to_profile_version),
						time: entry.timestamp || t('nldesign', 'unknown time'),
					}
				);
				historyList.appendChild(item);
			});
		} catch (error) {
			if (requestSequence !== historyRequestSequence) {
				return;
			}
			console.error('Could not load NL Design profile history.', error);
			renderListMessage(historyList, t('nldesign', 'Could not load profile history.'));
		}
	}

	function resetInstaller() {
		pendingPack = null;
		if (fileInput) {
			fileInput.value = '';
		}
		if (fileName) {
			fileName.textContent = t('nldesign', 'No file selected');
		}
		if (installSummary) {
			installSummary.textContent = '';
			installSummary.hidden = true;
		}
		if (installError) {
			installError.textContent = '';
			installError.hidden = true;
		}
		updateInstallerButton();
	}

	function showInstallError(message) {
		if (installError) {
			installError.textContent = message;
			installError.hidden = false;
		}
	}

	function updateInstallerButton() {
		if (installButton) {
			installButton.disabled = busy || pendingPack === null;
		}
	}

	function openInstaller() {
		if (!installerDialog || busy) {
			return;
		}
		resetInstaller();
		if (typeof installerDialog.showModal === 'function') {
			installerDialog.showModal();
		} else {
			installerDialog.setAttribute('open', 'open');
		}
	}

	function closeInstaller() {
		if (!installerDialog) {
			return;
		}
		if (typeof installerDialog.close === 'function') {
			installerDialog.close();
		} else {
			installerDialog.removeAttribute('open');
		}
		resetInstaller();
	}

	async function inspectSelectedPack() {
		pendingPack = null;
		updateInstallerButton();
		if (installError) {
			installError.hidden = true;
		}
		const file = fileInput && fileInput.files ? fileInput.files[0] : null;
		if (!file) {
			resetInstaller();
			return;
		}
		if (fileName) {
			fileName.textContent = file.name;
		}
		if (file.size <= 0 || file.size > MAX_PACK_BYTES) {
			showInstallError(t('nldesign', 'Profile pack must be no larger than 64 KiB.'));
			return;
		}

		try {
			const content = await file.text();
			if (!fileInput || !fileInput.files || fileInput.files[0] !== file) {
				return;
			}
			const decoded = JSON.parse(content);
			const profile = decoded && decoded.schema === 'nldesign-profile-pack/v1'
				? decoded.profile
				: null;
			if (!profile
				|| typeof profile.id !== 'string'
				|| typeof profile.version !== 'string'
				|| typeof profile.name !== 'string'
				|| !PROFILE_ID_PATTERN.test(profile.id)
				|| !VERSION_PATTERN.test(profile.version)
			) {
				throw new Error('Unsupported profile-pack envelope.');
			}

			pendingPack = content;
			if (installSummary) {
				installSummary.textContent = '';
				const title = document.createElement('strong');
				const identity = document.createElement('span');
				title.textContent = profile.name;
				identity.textContent = `${profile.id} · v${profile.version}`;
				installSummary.append(title, identity);
				installSummary.hidden = false;
			}
			updateInstallerButton();
		} catch (error) {
			console.error('Could not inspect the selected NL Design profile pack.', error);
			showInstallError(t('nldesign', 'This file is not a supported profile pack.'));
		}
	}

	async function installProfile() {
		if (busy || pendingPack === null) {
			return;
		}
		setBusy(true);
		setStatus(t('nldesign', 'Validating and installing profile…'));
		try {
			const data = await requestJson('/api/v1/profiles/install', {
				method: 'POST',
				body: { profilePack: pendingPack },
			});
			if (Array.isArray(data.profiles)) {
				setProfiles(data.profiles);
			}
			closeInstaller();
			notify(data.status === 'noop'
				? t('nldesign', 'This exact profile version was already installed.')
				: t('nldesign', 'Profile version installed.'));
		} catch (error) {
			console.error('Could not install the NL Design profile pack.', error);
			const message = error.payload && typeof error.payload.message === 'string'
				? error.payload.message
				: error.message || t('nldesign', 'Could not install the profile pack.');
			showInstallError(message);
			setStatus(t('nldesign', 'Profile installation failed.'));
		} finally {
			setBusy(false);
		}
	}

	async function uninstallProfile(profile) {
		if (busy || profile.origin !== 'installed') {
			return;
		}
		const confirmed = window.confirm(t(
			'nldesign',
			'Uninstall {profile} v{version}? Other versions remain available.',
			{ profile: profile.name || profile.id, version: profile.version }
		));
		if (!confirmed) {
			return;
		}

		setBusy(true);
		setStatus(t('nldesign', 'Uninstalling profile version…'));
		try {
			const data = await requestJson('/api/v1/profiles/uninstall', {
				method: 'POST',
				body: {
					profileId: profile.id,
					profileVersion: profile.version,
				},
			});
			if (Array.isArray(data.profiles)) {
				setProfiles(data.profiles);
			}
			notify(t('nldesign', 'Profile version uninstalled.'));
		} catch (error) {
			console.error('Could not uninstall the NL Design profile version.', error);
			notify(error.message || t('nldesign', 'Could not uninstall the profile version.'));
		} finally {
			setBusy(false);
		}
	}

	searchInput?.addEventListener('input', renderLibrary);
	rollbackButton?.addEventListener('click', () => void rollbackProfile());
	deactivateButton?.addEventListener('click', () => void saveProfile(null, null));
	openInstallerButton?.addEventListener('click', openInstaller);
	closeInstallerButton?.addEventListener('click', closeInstaller);
	cancelInstallerButton?.addEventListener('click', closeInstaller);
	fileInput?.addEventListener('change', () => void inspectSelectedPack());
	installButton?.addEventListener('click', () => void installProfile());
	installerDialog?.addEventListener('cancel', (event) => {
		event.preventDefault();
		closeInstaller();
	});
	modeButtons.forEach((button) => {
		button.addEventListener('click', () => {
			previewMode = button.dataset.previewMode === 'dark' ? 'dark' : 'light';
			updatePreview(getActiveMetadata());
		});
	});

	applyState({
		status: 'ok',
		tokenSet: activeProfile === '' ? null : activeProfile,
		profileVersion: activeProfile === '' ? null : activeVersion,
		revision: profileRevision,
		previousProfile: canRollback && previousProfile !== '' ? previousProfile : null,
		previousProfileVersion: canRollback && previousVersion !== '' ? previousVersion : null,
		canRollback,
	});
	void refreshThemingPlan(activeProfile, activeVersion);
	void refreshHistory();
});
