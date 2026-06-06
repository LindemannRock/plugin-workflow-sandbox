# Branch Release Tests

This repository verifies Release Please behavior for LindemannRock plugin
version lines.

## Branch Matrix

`main` always represents the latest stable Craft major. Real plugin repositories
should not create a `craft-5` branch while Craft 5 is the latest stable line on
`main`; this test repository includes `craft-5` only to prove the future
long-term maintenance flow before Craft 6 promotion.

| Branch | Purpose | Expected version shape |
| --- | --- | --- |
| `main` | Latest stable line, currently Craft 5 | `5.x.y` |
| `craft-5` | Future Craft 5 long-term maintenance line | `5.x.y` |
| `craft-6` | Future Craft 6 stable line | `6.x.y` |
| `craft-6-alpha` | Craft 6 alpha pre-release line | `6.0.0-alpha.n` |
| `craft-6-beta` | Craft 6 beta pre-release line | `6.0.0-beta.n` |

## Workflow Expectations

The workflow must:

- run on `main`, `craft-5`, `craft-6`, `craft-6-alpha`, and `craft-6-beta`;
- pass `target-branch: ${{ github.ref_name }}` to Release Please;
- use the default config for stable branches;
- use `release-please-config.alpha.json` for `craft-6-alpha`;
- use `release-please-config.beta.json` for `craft-6-beta`;
- set both `prerelease: true` and `versioning: prerelease` in alpha/beta
  configs;
- keep changelog headings in Craft Plugin Store format:
  `## [X.Y.Z](url) - YYYY-MM-DD`.

## Branch Protection

The test repo uses the `CI / static-analysis` repository ruleset for active
release branches:

```text
refs/heads/main
refs/heads/craft-5
refs/heads/craft-6
refs/heads/craft-6-alpha
refs/heads/craft-6-beta
```

Rules:

- block branch deletion;
- block non-fast-forward updates;
- require the `static-analysis` status check;
- no bypass actors.

Verified on 2026-06-06:

| Test | Result |
| --- | --- |
| Direct push to protected `main` | Rejected with missing `static-analysis`. |
| PR `#16` into `main` with passing `static-analysis` | Merge allowed. |
| PR `#17` into `main` with failing `static-analysis` | Merge blocked by branch policy. |

This confirms the required day-to-day flow: push work to a short-lived branch,
open a PR into the target release branch, wait for `static-analysis`, then merge.

## Version Baselines

Each branch must have its own `.release-please-manifest.json` and
`composer.json` version baseline before testing releases. This prevents multiple
branches from trying to create the same tag.

Suggested baselines:

| Branch | Manifest/composer baseline |
| --- | --- |
| `main` | `5.0.1` |
| `craft-5` | `5.1.0` |
| `craft-6` | `6.0.0` |
| `craft-6-alpha` | `6.0.0-alpha.0` |
| `craft-6-beta` | `6.0.0-beta.0` |

## Test Commits

Use one conventional commit per branch:

| Branch | Commit | Expected Release Please result |
| --- | --- | --- |
| `main` | `fix: exercise main release line` | Stable patch release on `main`. |
| `craft-5` | `fix: exercise Craft 5 maintenance release line` | Stable patch release on `craft-5`. |
| `craft-6` | `feat: exercise Craft 6 release line` | Stable minor release on `craft-6`. |
| `craft-6-alpha` | `feat: exercise Craft 6 alpha release line` | `6.0.0-alpha.1`. |
| `craft-6-beta` | `feat: exercise Craft 6 beta release line` | `6.0.0-beta.1`. |

After each push, verify that Release Please opens a release PR against the same
branch that received the commit.

After merging each release PR, verify:

- the tag does not collide with another branch's tag;
- GitHub marks alpha/beta releases as pre-releases;
- `composer.json` and `.release-please-manifest.json` update together;
- `CHANGELOG.md` has the expected section and heading format.

## Remote Test Results

Verified on GitHub Actions on 2026-06-06:

| Branch | Release PR | Tag/release | GitHub Release state |
| --- | --- | --- | --- |
| `main` | `#5` | `v5.0.1` | Stable |
| `craft-5` | `#10` | `v5.1.1` | Stable |
| `craft-6` | `#7` | `v6.1.0` | Stable, latest |
| `craft-6-alpha` | `#8` | `v6.0.0-alpha.1` | Prerelease |
| `craft-6-beta` | `#9` | `v6.0.0-beta.1` | Prerelease |

Initial alpha/beta test runs without `versioning: prerelease` produced
`6.1.0-alpha.0` and `6.1.0-beta.0`. Adding the prerelease versioning strategy
corrected the open PRs to `6.0.0-alpha.1` and `6.0.0-beta.1`.

After merging all five Release Please PRs, the post-merge workflows succeeded
and created the expected GitHub Releases. GitHub marked `v6.0.0-alpha.1` and
`v6.0.0-beta.1` as pre-releases.

The earlier `craft-4` test run was historical only and has been replaced by
`craft-5` in the active matrix. The LindemannRock plugin suite does not support
Craft 4.

The `craft-5` maintenance release was initially marked as GitHub's Latest
release because it was published after `v6.1.0`. This was corrected manually:

```bash
gh release edit v6.1.0 --latest
```

After the correction, `v6.1.0` is Latest and `v5.1.1` remains a normal stable
maintenance release.
