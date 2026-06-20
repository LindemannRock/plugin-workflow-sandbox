# Plugin Workflow Sandbox

Throwaway plugin for verifying:

1. **Release-please manifest-mode config** (`release-please-config.json` + `.release-please-manifest.json`)
2. **Keepachangelog section names** (`Added` / `Fixed` / `Security` / `Changed` / `Reverted`)
3. **CHANGELOG date format compliance** with the Craft Plugin Store (sed post-process step in the workflow rewrites `## [X.Y.Z](url) (YYYY-MM-DD)` → `## [X.Y.Z](url) - YYYY-MM-DD`)
4. **SECURITY.md** + `.gitattributes` export-ignore patterns
5. **Branch-specific release lines** for `main`, `craft-5`, `craft-6`,
   `craft-6-alpha`, and `craft-6-beta`
6. **Craft compatibility floor validation** using a disposable Craft install
   created by `scripts/test-craft-compat`
7. **Plugin smoke-test validation** through `scripts/smoke-test`

This plugin is not intended for distribution. Do not list on the Plugin Store.

## Setup

```bash
cd plugins/plugin-workflow-sandbox
git init
git add .
git commit -m "chore: initial test plugin scaffold"
git branch -M main
git remote add origin https://github.com/LindemannRock/craft-plugin-workflow-sandbox.git
git push -u origin main
```

Then enable **Settings → Security → "Enable private vulnerability reporting"** on the GitHub repo so SECURITY.md links work.

## Testing recipe

For branch release testing, start with [`BRANCH_RELEASE_TESTS.md`](BRANCH_RELEASE_TESTS.md).

For Craft compatibility floor testing, start with:

```bash
cd plugins/plugin-workflow-sandbox
scripts/test-craft-compat '^5.10'
scripts/test-craft-compat '^5.10' dev-main --install
scripts/test-craft-compat '^5.10' dev-main --install --php-version 8.3
scripts/test-craft-compat '5.10.0' dev-main --allow-insecure-floor
```

Use the exact-version form only to probe a historical floor. Normal release
checks should use the latest secure patch in the supported Craft minor. The
script pins Composer's platform PHP to the selected DDEV PHP version so the
dependency set matches the runtime. In `--install` mode, the script runs
`scripts/smoke-test` from the mirrored plugin package after Craft installs and
the plugin is enabled.

Push commits in this order to verify every section + version-bump behaviour:

| Commit | Expected behaviour |
| ------ | ------------------ |
| `feat: add hello world` | Release PR opens. Version: 1.0.0 → 1.1.0. CHANGELOG entry shows `### Added`. |
| `fix: handle null input` | Next release PR. Version: 1.1.0 → 1.1.1. CHANGELOG entry shows `### Fixed`. |
| `security: patch XSS in widget output` | Next release PR. Version: 1.1.1 → 1.1.2. CHANGELOG entry shows `### Security`. |
| `perf: cache lookup table` | Next release PR. Version: 1.1.2 → 1.1.3. CHANGELOG entry shows `### Changed`. |
| `revert: roll back hello world` | Next release PR. Version: 1.1.3 → 1.1.4. CHANGELOG entry shows `### Reverted`. |
| `chore: bump dependency` | No release PR (chore alone doesn't bump version). |
| `docs: clarify README` | No release PR. |
| `feat: add second feature` + `chore: cleanup` | Release PR opens for the feat. Chore does NOT appear in CHANGELOG. |

After each release PR appears, verify:

- [ ] CHANGELOG heading reads `## [X.Y.Z](url) - YYYY-MM-DD` (hyphen, not parens). If still parens, the sed step failed.
- [ ] Section name matches the table above (`Added`, not `Features`).
- [ ] Hidden types (chore, docs, etc.) don't render as sections.
- [ ] `.release-please-manifest.json` updates to the new version after merge.
- [ ] `composer.json` `version` field updates to the new version after merge.

## Breaking changes (skip if testing pre-release flow)

`feat!: rewrite API surface` or any commit body containing `BREAKING CHANGE:` will bump major (1.x.x → 2.0.0). Only use this if testing post-1.0 release behaviour. For pre-release plugins (0.x.x), avoid `!` and `BREAKING CHANGE:` entirely.

## Cleanup

When done testing, delete the GitHub repo and remove the local directory. Nothing in this plugin is reused elsewhere.
