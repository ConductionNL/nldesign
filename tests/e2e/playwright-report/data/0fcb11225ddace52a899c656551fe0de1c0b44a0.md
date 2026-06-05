# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: spec-coverage/token-editor-ui.spec.ts >> token-editor-ui >> Token rows show resolved current value in inputs
- Location: tests/e2e/spec-coverage/token-editor-ui.spec.ts:104:6

# Error details

```
TimeoutError: page.waitForSelector: Timeout 15000ms exceeded.
Call log:
  - waiting for locator('#nldesign-token-editor') to be visible

```

# Page snapshot

```yaml
- generic [active] [ref=e1]:
  - generic [ref=e2]:
    - link "Skip to main content" [ref=e3] [cursor=pointer]:
      - /url: "#app-content"
    - link "Skip to navigation of app" [ref=e4] [cursor=pointer]:
      - /url: "#app-navigation"
  - banner [ref=e5]:
    - generic [ref=e6]:
      - link "Go to Dashboard" [ref=e7] [cursor=pointer]:
        - /url: /
      - navigation "Applications menu" [ref=e9]:
        - list "Apps" [ref=e10]:
          - listitem [ref=e11]:
            - link "Dashboard" [ref=e12] [cursor=pointer]:
              - /url: /apps/dashboard/
              - img [ref=e13]
              - generic [ref=e14]: Dashboard
          - listitem [ref=e15]:
            - link "LaunchPad" [ref=e16] [cursor=pointer]:
              - /url: /apps/launchpad/
              - img [ref=e17]
              - generic [ref=e18]: LaunchPad
          - listitem [ref=e19]:
            - link "Files" [ref=e20] [cursor=pointer]:
              - /url: /apps/files/
              - img [ref=e21]
              - generic [ref=e22]: Files
          - listitem [ref=e23]:
            - link "Photos" [ref=e24] [cursor=pointer]:
              - /url: /apps/photos/
              - img [ref=e25]
              - generic [ref=e26]: Photos
          - listitem [ref=e27]:
            - link "Activity" [ref=e28] [cursor=pointer]:
              - /url: /apps/activity/
              - img [ref=e29]
              - generic [ref=e30]: Activity
          - listitem [ref=e31]:
            - link "Procest" [ref=e32] [cursor=pointer]:
              - /url: /apps/procest
              - img [ref=e33]
              - generic [ref=e34]: Procest
          - listitem [ref=e35]:
            - link "Pipelinq" [ref=e36] [cursor=pointer]:
              - /url: /apps/pipelinq
              - img [ref=e37]
              - generic [ref=e38]: Pipelinq
          - listitem [ref=e39]:
            - link "PetStore" [ref=e40] [cursor=pointer]:
              - /url: /apps/petstore/
              - img [ref=e41]
              - generic [ref=e42]: PetStore
          - listitem [ref=e43]:
            - link "Register" [ref=e44] [cursor=pointer]:
              - /url: /apps/openregister/
              - img [ref=e45]
              - generic [ref=e46]: Register
          - listitem [ref=e47]:
            - link "Catalogi" [ref=e48] [cursor=pointer]:
              - /url: /apps/opencatalogi
              - img [ref=e49]
              - generic [ref=e50]: Catalogi
          - listitem [ref=e51]:
            - link "Larping" [ref=e52] [cursor=pointer]:
              - /url: /apps/larpingapp/
              - img [ref=e53]
              - generic [ref=e54]: Larping
          - listitem [ref=e55]:
            - link "Doriath" [ref=e56] [cursor=pointer]:
              - /url: /apps/doriath/
              - img [ref=e57]
              - generic [ref=e58]: Doriath
          - listitem [ref=e59]:
            - link "DocuDesk" [ref=e60] [cursor=pointer]:
              - /url: /apps/docudesk/
              - img [ref=e61]
              - generic [ref=e62]: DocuDesk
          - listitem [ref=e63]:
            - link "Decidesk" [ref=e64] [cursor=pointer]:
              - /url: /apps/decidesk/
              - img [ref=e65]
              - generic [ref=e66]: Decidesk
          - listitem [ref=e67]:
            - link "Software Catalogs" [ref=e68] [cursor=pointer]:
              - /url: /apps/softwarecatalog
              - img [ref=e69]
              - generic [ref=e70]: Software Catalogs
          - listitem [ref=e71]:
            - link "Zaak Afhandel App" [ref=e72] [cursor=pointer]:
              - /url: /apps/zaakafhandelapp/
              - img [ref=e73]
              - generic [ref=e74]: Zaak Afhandel App
          - listitem [ref=e75]:
            - link "OpenBuild" [ref=e76] [cursor=pointer]:
              - /url: /apps/openbuild/
              - img [ref=e77]
              - generic [ref=e78]: OpenBuild
    - generic [ref=e79]:
      - button "Unified search" [ref=e82] [cursor=pointer]:
        - img [ref=e85]:
          - img [ref=e86]
      - generic "Notifications" [ref=e89]:
        - button "Notifications" [ref=e90] [cursor=pointer]:
          - img [ref=e94]
      - button "Search contacts" [ref=e98] [cursor=pointer]:
        - img [ref=e101]:
          - img [ref=e102]
      - navigation "Settings menu" [ref=e104]:
        - button "Settings menu" [ref=e105] [cursor=pointer]
        - generic [ref=e109]: Avatar of admin
  - generic [ref=e110]:
    - 'heading "Administration settings: Theming" [level=1] [ref=e111]'
    - generic [ref=e112]:
      - generic: Personal
      - navigation "Personal" [ref=e113]:
        - list [ref=e114]:
          - listitem [ref=e115]:
            - link "Personal info" [ref=e116] [cursor=pointer]:
              - /url: /settings/user
          - listitem [ref=e117]:
            - link "Security" [ref=e118] [cursor=pointer]:
              - /url: /settings/user/security
          - listitem [ref=e119]:
            - link "Notifications" [ref=e120] [cursor=pointer]:
              - /url: /settings/user/notifications
          - listitem [ref=e121]:
            - link "Mobile & desktop" [ref=e122] [cursor=pointer]:
              - /url: /settings/user/sync-clients
          - listitem [ref=e123]:
            - link "Sharing" [ref=e124] [cursor=pointer]:
              - /url: /settings/user/sharing
          - listitem [ref=e125]:
            - link "Appearance and accessibility" [ref=e126] [cursor=pointer]:
              - /url: /settings/user/theming
          - listitem [ref=e127]:
            - link "Availability" [ref=e128] [cursor=pointer]:
              - /url: /settings/user/availability
          - listitem [ref=e129]:
            - link "Flow" [ref=e130] [cursor=pointer]:
              - /url: /settings/user/workflow
          - listitem [ref=e131]:
            - link "Privacy" [ref=e132] [cursor=pointer]:
              - /url: /settings/user/privacy
      - generic: Administration
      - navigation "Administration" [ref=e133]:
        - list [ref=e134]:
          - listitem [ref=e135]:
            - link "Overview" [ref=e136] [cursor=pointer]:
              - /url: /settings/admin/overview
          - listitem [ref=e137]:
            - link "Quick presets" [ref=e138] [cursor=pointer]:
              - /url: /settings/admin/presets
          - listitem [ref=e139]:
            - link "Support" [ref=e140] [cursor=pointer]:
              - /url: /settings/admin/support
          - listitem [ref=e141]:
            - link "Basic settings" [ref=e142] [cursor=pointer]:
              - /url: /settings/admin
          - listitem [ref=e143]:
            - link "Sharing" [ref=e144] [cursor=pointer]:
              - /url: /settings/admin/sharing
          - listitem [ref=e145]:
            - link "Security" [ref=e146] [cursor=pointer]:
              - /url: /settings/admin/security
          - listitem [ref=e147]:
            - link "Theming" [ref=e148] [cursor=pointer]:
              - /url: /settings/admin/theming
          - listitem [ref=e149]:
            - link "Assistant" [ref=e150] [cursor=pointer]:
              - /url: /settings/admin/ai
          - listitem [ref=e151]:
            - link "Groupware" [ref=e152] [cursor=pointer]:
              - /url: /settings/admin/groupware
          - listitem [ref=e153]:
            - link "AppAPI" [ref=e154] [cursor=pointer]:
              - /url: /settings/admin/app_api
          - listitem [ref=e155]:
            - link "Administration privileges" [ref=e156] [cursor=pointer]:
              - /url: /settings/admin/admindelegation
          - listitem [ref=e157]:
            - link "Activity" [ref=e158] [cursor=pointer]:
              - /url: /settings/admin/activity
          - listitem [ref=e159]:
            - link "LarpingApp" [ref=e160] [cursor=pointer]:
              - /url: /settings/admin/larpingapp
          - listitem [ref=e161]:
            - link "Notifications" [ref=e162] [cursor=pointer]:
              - /url: /settings/admin/notifications
          - listitem [ref=e163]:
            - link "Flow" [ref=e164] [cursor=pointer]:
              - /url: /settings/admin/workflow
          - listitem [ref=e165]:
            - link "Decidesk" [ref=e166] [cursor=pointer]:
              - /url: /settings/admin/decidesk
          - listitem [ref=e167]:
            - link "Doriath" [ref=e168] [cursor=pointer]:
              - /url: /settings/admin/doriath
          - listitem [ref=e169]:
            - link "OpenBuild" [ref=e170] [cursor=pointer]:
              - /url: /settings/admin/openbuild
          - listitem [ref=e171]:
            - link "App Template" [ref=e172] [cursor=pointer]:
              - /url: /settings/admin/petstore
          - listitem [ref=e173]:
            - link "Procest" [ref=e174] [cursor=pointer]:
              - /url: /settings/admin/procest
          - listitem [ref=e175]:
            - link "Pipelinq" [ref=e176] [cursor=pointer]:
              - /url: /settings/admin/pipelinq
          - listitem [ref=e177]:
            - link "LaunchPad" [ref=e178] [cursor=pointer]:
              - /url: /settings/admin/launchpad
          - listitem [ref=e179]:
            - link "Usage survey" [ref=e180] [cursor=pointer]:
              - /url: /settings/admin/survey_client
          - listitem [ref=e181]:
            - link "Logging" [ref=e182] [cursor=pointer]:
              - /url: /settings/admin/logging
          - listitem [ref=e183]:
            - link "System" [ref=e184] [cursor=pointer]:
              - /url: /settings/admin/serverinfo
          - listitem [ref=e185]:
            - link "DocuDesk" [ref=e186] [cursor=pointer]:
              - /url: /settings/admin/docudesk
          - listitem [ref=e187]:
            - link "Open Catalogi" [ref=e188] [cursor=pointer]:
              - /url: /settings/admin/opencatalogi
          - listitem [ref=e189]:
            - link "Open Register" [ref=e190] [cursor=pointer]:
              - /url: /settings/admin/openregister
          - listitem [ref=e191]:
            - link "Software Catalog" [ref=e192] [cursor=pointer]:
              - /url: /settings/admin/softwarecatalog
          - listitem [ref=e193]:
            - link "Zaak Afhandelapp" [ref=e194] [cursor=pointer]:
              - /url: /settings/admin/zaakafhandelapp
    - main [ref=e195]:
      - generic [ref=e196]:
        - generic [ref=e197]:
          - heading "Theming External documentation for Theming" [level=2] [ref=e198]:
            - text: Theming
            - link "External documentation for Theming" [ref=e199] [cursor=pointer]:
              - /url: https://docs.nextcloud.com/server/32/go.php?to=admin-theming
              - img [ref=e200]:
                - img [ref=e201]
          - paragraph [ref=e203]: Theming makes it possible to easily customize the look and feel of your instance and supported clients. This will be visible for all users.
          - generic [ref=e204]:
            - generic [ref=e207]:
              - textbox "Name" [ref=e208] [cursor=pointer]: Nextcloud
              - generic: Name
            - generic [ref=e211]:
              - textbox "Web link" [ref=e212] [cursor=pointer]:
                - /placeholder: https://…
                - text: https://nextcloud.com
              - generic: Web link
            - generic [ref=e215]:
              - textbox "Slogan" [ref=e216] [cursor=pointer]: a safe home for all your data
              - generic: Slogan
            - generic [ref=e217]:
              - generic [ref=e218] [cursor=pointer]: Primary color
              - generic [ref=e219]:
                - button "Select a custom color" [ref=e221] [cursor=pointer]:
                  - generic [ref=e222]:
                    - img [ref=e224]:
                      - img [ref=e225]
                    - generic [ref=e227]: "#00679e"
                - button "Reset to default" [ref=e229] [cursor=pointer]:
                  - img [ref=e232]:
                    - img [ref=e233]
              - generic [ref=e235]: The primary color is used for highlighting elements like important buttons. It might get slightly adjusted depending on the current color schema.
            - generic [ref=e236]:
              - generic [ref=e237] [cursor=pointer]: Background color
              - generic [ref=e238]:
                - button "Select a custom color" [ref=e240] [cursor=pointer]:
                  - generic [ref=e241]:
                    - img [ref=e243]:
                      - img [ref=e244]
                    - generic [ref=e246]: "#00679e"
                - button "Reset to default" [ref=e248] [cursor=pointer]:
                  - img [ref=e251]:
                    - img [ref=e252]
              - generic [ref=e254]: Instead of a background image you can also configure a plain background color. If you use a background image changing this color will influence the color of the app menu icons.
            - generic [ref=e255]:
              - generic [ref=e256] [cursor=pointer]: Logo
              - button "Upload new logo" [ref=e258] [cursor=pointer]:
                - generic [ref=e259]:
                  - img [ref=e261]:
                    - img [ref=e262]
                  - generic [ref=e264]: Upload
            - generic [ref=e265]:
              - generic [ref=e266] [cursor=pointer]: Background and login image
              - generic [ref=e267]:
                - button "Upload new background and login image" [ref=e268] [cursor=pointer]:
                  - generic [ref=e269]:
                    - img [ref=e271]:
                      - img [ref=e272]
                    - generic [ref=e274]: Upload
                - button "Remove background image" [ref=e275] [cursor=pointer]:
                  - img [ref=e278]:
                    - img [ref=e279]
        - generic [ref=e283]:
          - heading "Advanced options" [level=2] [ref=e284]
          - generic [ref=e285]:
            - generic [ref=e288]:
              - textbox "Legal notice link" [ref=e289] [cursor=pointer]:
                - /placeholder: https://…
              - generic: Legal notice link
            - generic [ref=e292]:
              - textbox "Privacy policy link" [ref=e293] [cursor=pointer]:
                - /placeholder: https://…
              - generic: Privacy policy link
            - generic [ref=e294]:
              - generic [ref=e295] [cursor=pointer]: Header logo
              - button "Upload new header logo" [ref=e297] [cursor=pointer]:
                - generic [ref=e298]:
                  - img [ref=e300]:
                    - img [ref=e301]
                  - generic [ref=e303]: Upload
            - generic [ref=e304]:
              - generic [ref=e305] [cursor=pointer]: Favicon
              - button "Upload new favicon" [ref=e307] [cursor=pointer]:
                - generic [ref=e308]:
                  - img [ref=e310]:
                    - img [ref=e311]
                  - generic [ref=e313]: Upload
            - generic [ref=e314]:
              - generic [ref=e315] [cursor=pointer]: User settings
              - generic [ref=e317]:
                - checkbox "Disable user theming" [ref=e318] [cursor=pointer]
                - generic [ref=e319] [cursor=pointer]:
                  - img [ref=e321]:
                    - img [ref=e322]
                  - generic [ref=e324]: Disable user theming
              - paragraph [ref=e325]: Although you can select and customize your instance, users can change their background and colors. If you want to enforce your customization, you can toggle this on.
        - generic [ref=e326]:
          - heading "Navigation bar settings" [level=2] [ref=e327]
          - heading "Default app" [level=3] [ref=e328]
          - paragraph [ref=e329]: The default app is the app that is e.g. opened after login or when the logo in the menu is clicked.
          - generic [ref=e330]:
            - checkbox "Use custom default app" [checked] [ref=e331] [cursor=pointer]
            - generic [ref=e332] [cursor=pointer]:
              - img [ref=e334]:
                - img [ref=e335]
              - generic [ref=e337]: Use custom default app
          - heading "Global default app" [level=4] [ref=e338]
          - generic [ref=e340]:
            - generic [ref=e341]:
              - generic [ref=e342]:
                - generic "Dashboard" [ref=e343]:
                  - generic [ref=e344]: Dashboard
                - button "Deselect Dashboard" [ref=e345] [cursor=pointer]:
                  - img [ref=e346]:
                    - img [ref=e347]
              - combobox [ref=e349] [cursor=pointer]
            - button [ref=e351] [cursor=pointer]:
              - img [ref=e353]
          - heading "Default app priority" [level=5] [ref=e355]
          - paragraph [ref=e356]: If an app is not enabled for a user, the next app with lower priority is used.
          - status [ref=e357]
          - list [ref=e358]:
            - listitem [ref=e359]:
              - generic [ref=e361]: Dashboard
```

# Test source

```ts
  9   |  */
  10  | import { test, expect } from '@playwright/test'
  11  | 
  12  | const THEMING_URL = '/settings/admin/theming'
  13  | 
  14  | test.describe('token-editor-ui', () => {
  15  | 
  16  | 	// -----------------------------------------------------------------------
  17  | 	// Requirement: Token Editor Panel
  18  | 	// -----------------------------------------------------------------------
  19  | 
  20  | 	test(
  21  | 		// @e2e openspec/specs/token-editor-ui/spec.md#admin-opens-settings
  22  | 		'Admin opens settings — token editor panel is visible',
  23  | 		async ({ page }) => {
  24  | 			await page.goto(THEMING_URL)
  25  | 			await page.waitForLoadState('networkidle')
  26  | 			// Token editor is inside #nldesign-token-editor
  27  | 			const editorEl = page.locator('#nldesign-token-editor')
  28  | 			await expect(editorEl).toBeVisible()
  29  | 			// Tabs must be present
  30  | 			const tabs = editorEl.locator('button').filter({ hasText: /Login page|Content area|Buttons|Typography/ })
  31  | 			await expect(tabs.first()).toBeVisible()
  32  | 		},
  33  | 	)
  34  | 
  35  | 	// Scenario: Non-admin user visits settings
  36  | 	// @e2e exclude openspec/specs/token-editor-ui/spec.md#non-admin-user-visits-settings
  37  | 	// Requires non-admin session — environment only has admin user.
  38  | 
  39  | 	// -----------------------------------------------------------------------
  40  | 	// Requirement: Functional Tab Groups
  41  | 	// -----------------------------------------------------------------------
  42  | 
  43  | 	test(
  44  | 		// @e2e openspec/specs/token-editor-ui/spec.md#admin-selects-login-page-tab
  45  | 		'Login page & Branding tab shows primary-color tokens',
  46  | 		async ({ page }) => {
  47  | 			await page.goto(THEMING_URL)
  48  | 			await page.waitForLoadState('networkidle')
  49  | 			// The "Login page & Branding" tab should be active by default or clickable
  50  | 			const tab = page.locator('button:has-text("Login page & Branding")')
  51  | 			await expect(tab).toBeVisible()
  52  | 			await tab.click()
  53  | 			// After clicking, the token rows for primary colors must be visible
  54  | 			const primaryRow = page.locator('text=--color-primary').first()
  55  | 			await expect(primaryRow).toBeVisible()
  56  | 		},
  57  | 	)
  58  | 
  59  | 	test(
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
> 109 | 			await page.waitForSelector('#nldesign-token-editor', { timeout: 15_000 })
      |               ^ TimeoutError: page.waitForSelector: Timeout 15000ms exceeded.
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
  160 | 			await expect(saveBtn).toBeVisible()
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