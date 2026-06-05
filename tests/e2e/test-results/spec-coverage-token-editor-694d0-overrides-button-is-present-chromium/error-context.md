# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: spec-coverage/token-editor-ui.spec.ts >> token-editor-ui >> Save overrides button is present
- Location: tests/e2e/spec-coverage/token-editor-ui.spec.ts:153:6

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: locator('button:has-text("Save overrides")')
Expected: visible
Timeout: 10000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" with timeout 10000ms
  - waiting for locator('button:has-text("Save overrides")')

```

```yaml
- link "Skip to main content":
  - /url: "#app-content"
- link "Skip to navigation of app":
  - /url: "#app-navigation"
- banner:
  - link "Go to Dashboard":
    - /url: /
  - navigation "Applications menu":
    - list "Apps":
      - listitem:
        - link "Dashboard":
          - /url: /apps/dashboard/
      - listitem:
        - link "LaunchPad":
          - /url: /apps/launchpad/
      - listitem:
        - link "Files":
          - /url: /apps/files/
      - listitem:
        - link "Photos":
          - /url: /apps/photos/
      - listitem:
        - link "Activity":
          - /url: /apps/activity/
      - listitem:
        - link "Procest":
          - /url: /apps/procest
      - listitem:
        - link "Pipelinq":
          - /url: /apps/pipelinq
      - listitem:
        - link "PetStore":
          - /url: /apps/petstore/
      - listitem:
        - link "Register":
          - /url: /apps/openregister/
      - listitem:
        - link "Catalogi":
          - /url: /apps/opencatalogi
      - listitem:
        - link "Larping":
          - /url: /apps/larpingapp/
      - listitem:
        - link "Doriath":
          - /url: /apps/doriath/
      - listitem:
        - link "DocuDesk":
          - /url: /apps/docudesk/
      - listitem:
        - link "Decidesk":
          - /url: /apps/decidesk/
      - listitem:
        - link "Software Catalogs":
          - /url: /apps/softwarecatalog
      - listitem:
        - link "Zaak Afhandel App":
          - /url: /apps/zaakafhandelapp/
      - listitem:
        - link "OpenBuild":
          - /url: /apps/openbuild/
  - button "Unified search"
  - button "Notifications":
    - img
  - button "Search contacts"
  - navigation "Settings menu":
    - button "Settings menu"
    - text: Avatar of admin
- 'heading "Administration settings: Theming" [level=1]'
- text: Personal
- navigation "Personal":
  - list:
    - listitem:
      - link "Personal info":
        - /url: /settings/user
    - listitem:
      - link "Security":
        - /url: /settings/user/security
    - listitem:
      - link "Notifications":
        - /url: /settings/user/notifications
    - listitem:
      - link "Mobile & desktop":
        - /url: /settings/user/sync-clients
    - listitem:
      - link "Sharing":
        - /url: /settings/user/sharing
    - listitem:
      - link "Appearance and accessibility":
        - /url: /settings/user/theming
    - listitem:
      - link "Availability":
        - /url: /settings/user/availability
    - listitem:
      - link "Flow":
        - /url: /settings/user/workflow
    - listitem:
      - link "Privacy":
        - /url: /settings/user/privacy
- text: Administration
- navigation "Administration":
  - list:
    - listitem:
      - link "Overview":
        - /url: /settings/admin/overview
    - listitem:
      - link "Quick presets":
        - /url: /settings/admin/presets
    - listitem:
      - link "Support":
        - /url: /settings/admin/support
    - listitem:
      - link "Basic settings":
        - /url: /settings/admin
    - listitem:
      - link "Sharing":
        - /url: /settings/admin/sharing
    - listitem:
      - link "Security":
        - /url: /settings/admin/security
    - listitem:
      - link "Theming":
        - /url: /settings/admin/theming
    - listitem:
      - link "Assistant":
        - /url: /settings/admin/ai
    - listitem:
      - link "Groupware":
        - /url: /settings/admin/groupware
    - listitem:
      - link "AppAPI":
        - /url: /settings/admin/app_api
    - listitem:
      - link "Administration privileges":
        - /url: /settings/admin/admindelegation
    - listitem:
      - link "Activity":
        - /url: /settings/admin/activity
    - listitem:
      - link "LarpingApp":
        - /url: /settings/admin/larpingapp
    - listitem:
      - link "Notifications":
        - /url: /settings/admin/notifications
    - listitem:
      - link "Flow":
        - /url: /settings/admin/workflow
    - listitem:
      - link "Decidesk":
        - /url: /settings/admin/decidesk
    - listitem:
      - link "Doriath":
        - /url: /settings/admin/doriath
    - listitem:
      - link "OpenBuild":
        - /url: /settings/admin/openbuild
    - listitem:
      - link "App Template":
        - /url: /settings/admin/petstore
    - listitem:
      - link "Procest":
        - /url: /settings/admin/procest
    - listitem:
      - link "Pipelinq":
        - /url: /settings/admin/pipelinq
    - listitem:
      - link "LaunchPad":
        - /url: /settings/admin/launchpad
    - listitem:
      - link "Usage survey":
        - /url: /settings/admin/survey_client
    - listitem:
      - link "Logging":
        - /url: /settings/admin/logging
    - listitem:
      - link "System":
        - /url: /settings/admin/serverinfo
    - listitem:
      - link "DocuDesk":
        - /url: /settings/admin/docudesk
    - listitem:
      - link "Open Catalogi":
        - /url: /settings/admin/opencatalogi
    - listitem:
      - link "Open Register":
        - /url: /settings/admin/openregister
    - listitem:
      - link "Software Catalog":
        - /url: /settings/admin/softwarecatalog
    - listitem:
      - link "Zaak Afhandelapp":
        - /url: /settings/admin/zaakafhandelapp
- main:
  - heading "Theming External documentation for Theming" [level=2]:
    - text: Theming
    - link "External documentation for Theming":
      - /url: https://docs.nextcloud.com/server/32/go.php?to=admin-theming
  - paragraph: Theming makes it possible to easily customize the look and feel of your instance and supported clients. This will be visible for all users.
  - textbox "Name": Nextcloud
  - text: Name
  - textbox "Web link":
    - /placeholder: https://…
    - text: https://nextcloud.com
  - text: Web link
  - textbox "Slogan": a safe home for all your data
  - text: Slogan Primary color
  - button "Select a custom color": "#00679e"
  - button "Reset to default"
  - text: The primary color is used for highlighting elements like important buttons. It might get slightly adjusted depending on the current color schema. Background color
  - button "Select a custom color": "#00679e"
  - button "Reset to default"
  - text: Instead of a background image you can also configure a plain background color. If you use a background image changing this color will influence the color of the app menu icons. Logo
  - button "Upload new logo": Upload
  - text: Background and login image
  - button "Upload new background and login image": Upload
  - button "Remove background image"
  - heading "Advanced options" [level=2]
  - textbox "Legal notice link":
    - /placeholder: https://…
  - text: Legal notice link
  - textbox "Privacy policy link":
    - /placeholder: https://…
  - text: Privacy policy link Header logo
  - button "Upload new header logo": Upload
  - text: Favicon
  - button "Upload new favicon": Upload
  - text: User settings
  - checkbox "Disable user theming"
  - text: Disable user theming
  - paragraph: Although you can select and customize your instance, users can change their background and colors. If you want to enforce your customization, you can toggle this on.
  - heading "Navigation bar settings" [level=2]
  - heading "Default app" [level=3]
  - paragraph: The default app is the app that is e.g. opened after login or when the logo in the menu is clicked.
  - checkbox "Use custom default app" [checked]
  - text: Use custom default app
  - heading "Global default app" [level=4]
  - text: Dashboard
  - button "Deselect Dashboard"
  - combobox
  - button
  - heading "Default app priority" [level=5]
  - paragraph: If an app is not enabled for a user, the next app with lower priority is used.
  - status
  - list:
    - listitem: Dashboard
```

# Test source

```ts
  60  | 		// @e2e openspec/specs/token-editor-ui/spec.md#every-editable-token-appears-in-exactly-one-tab
  61  | 		'All four tabs are rendered in token editor',
  62  | 		async ({ page }) => {
  63  | 			await page.goto(THEMING_URL)
  64  | 			await page.waitForLoadState('networkidle')
  65  | 			await expect(page.locator('button:has-text("Login page & Branding")')).toBeVisible()
  66  | 			await expect(page.locator('button:has-text("Content area")')).toBeVisible()
  67  | 			await expect(page.locator('button:has-text("Buttons & Status")')).toBeVisible()
  68  | 			await expect(page.locator('button:has-text("Typography")')).toBeVisible()
  69  | 		},
  70  | 	)
  71  | 
  72  | 	// -----------------------------------------------------------------------
  73  | 	// Requirement: Excluded Token Registry
  74  | 	// -----------------------------------------------------------------------
  75  | 
  76  | 	// Scenario: Admin attempts to set excluded token via API
  77  | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#admin-attempts-to-set-excluded-token-via-api
  78  | 	// Tests API rejection of excluded tokens — backend validation, not UI.
  79  | 
  80  | 	test(
  81  | 		// @e2e openspec/specs/token-editor-ui/spec.md#excluded-tokens-are-not-shown-in-ui
  82  | 		'Excluded tokens like --color-main-background are not shown in any tab',
  83  | 		async ({ page }) => {
  84  | 			await page.goto(THEMING_URL)
  85  | 			await page.waitForLoadState('networkidle')
  86  | 			// Check all tabs for the excluded token
  87  | 			const tabs = ['Login page & Branding', 'Content area', 'Buttons & Status', 'Typography']
  88  | 			for (const tabText of tabs) {
  89  | 				const tab = page.locator(`button:has-text("${tabText}")`)
  90  | 				if (await tab.count() > 0) {
  91  | 					await tab.click()
  92  | 				}
  93  | 			}
  94  | 			// --color-main-background must not appear as an editable row label
  95  | 			const excludedRow = page.locator('text=--color-main-background').first()
  96  | 			await expect(excludedRow).not.toBeVisible()
  97  | 		},
  98  | 	)
  99  | 
  100 | 	// -----------------------------------------------------------------------
  101 | 	// Requirement: Editable Token Input
  102 | 	// -----------------------------------------------------------------------
  103 | 
  104 | 	test(
  105 | 		// @e2e openspec/specs/token-editor-ui/spec.md#token-shows-resolved-current-value
  106 | 		'Token rows show resolved current value in inputs',
  107 | 		async ({ page }) => {
  108 | 			await page.goto(THEMING_URL)
  109 | 			await page.waitForSelector('#nldesign-token-editor', { timeout: 15_000 })
  110 | 			// Click Login page tab
  111 | 			await page.locator('button:has-text("Login page & Branding")').click()
  112 | 			// At least one color text input inside the token editor must have a non-empty value
  113 | 			// The token editor renders <input type="text" class="nldesign-color-text"> elements
  114 | 			const colorInput = page.locator('.nldesign-color-text, .nldesign-text-input').first()
  115 | 			await expect(colorInput).toBeAttached({ timeout: 5_000 })
  116 | 			const val = await colorInput.inputValue()
  117 | 			expect(val.trim().length).toBeGreaterThan(0)
  118 | 		},
  119 | 	)
  120 | 
  121 | 	// Scenario: Token shows custom value indicator
  122 | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#token-shows-custom-value-indicator
  123 | 	// Requires a known custom override to be set in custom-overrides.css first —
  124 | 	// environment may not have customizations in place.
  125 | 
  126 | 	// Scenario: Color tokens render a color picker
  127 | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#color-tokens-render-a-color-picker
  128 | 	// Colour picker is a native <input type="color"> element; its presence
  129 | 	// alongside the hex text field is verified by checking both inputs render.
  130 | 
  131 | 	// Scenario: Non-color tokens render a text input
  132 | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#non-color-tokens-render-a-text-input
  133 | 	// Requires inspecting specific non-color token rows; covered partially by
  134 | 	// the border-radius rows verified in content-area tab test.
  135 | 
  136 | 	// -----------------------------------------------------------------------
  137 | 	// Requirement: Live Preview
  138 | 	// -----------------------------------------------------------------------
  139 | 
  140 | 	// Scenario: Admin changes a color token
  141 | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#admin-changes-a-color-token
  142 | 	// Requires modifying a token value and verifying live CSS update — would alter
  143 | 	// visible state of shared env; covered by per-token reset test below.
  144 | 
  145 | 	// Scenario: Unsaved changes are lost on reload
  146 | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#unsaved-changes-are-lost-on-reload
  147 | 	// Side-effect test that reloads the page and loses state — not safe in parallel runs.
  148 | 
  149 | 	// -----------------------------------------------------------------------
  150 | 	// Requirement: Save Action
  151 | 	// -----------------------------------------------------------------------
  152 | 
  153 | 	test(
  154 | 		// @e2e openspec/specs/token-editor-ui/spec.md#admin-saves-changes
  155 | 		'Save overrides button is present',
  156 | 		async ({ page }) => {
  157 | 			await page.goto(THEMING_URL)
  158 | 			await page.waitForLoadState('networkidle')
  159 | 			const saveBtn = page.locator('button:has-text("Save overrides")')
> 160 | 			await expect(saveBtn).toBeVisible()
      |                          ^ Error: expect(locator).toBeVisible() failed
  161 | 		},
  162 | 	)
  163 | 
  164 | 	// Scenario: Save with no changes
  165 | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#save-with-no-changes
  166 | 	// Clicking Save writes custom-overrides.css — avoid mutating shared env state.
  167 | 
  168 | 	// -----------------------------------------------------------------------
  169 | 	// Requirement: Per-Token Reset
  170 | 	// -----------------------------------------------------------------------
  171 | 
  172 | 	test(
  173 | 		// @e2e openspec/specs/token-editor-ui/spec.md#admin-resets-a-customized-token
  174 | 		'Per-token reset buttons are present in the editor',
  175 | 		async ({ page }) => {
  176 | 			await page.goto(THEMING_URL)
  177 | 			await page.waitForLoadState('networkidle')
  178 | 			await page.locator('button:has-text("Login page & Branding")').click()
  179 | 			// Each token row must have a reset button (↺)
  180 | 			const resetBtns = page.locator('button:has-text("↺")')
  181 | 			const count = await resetBtns.count()
  182 | 			expect(count).toBeGreaterThan(0)
  183 | 		},
  184 | 	)
  185 | 
  186 | })
  187 | 
```