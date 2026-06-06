# Branch Release Tests

This repository verifies Release Please behavior for LindemannRock plugin
version lines.

## Branch Matrix

`main` always represents the latest stable Craft major. Do not create a
`craft-5` branch while Craft 5 is the latest stable line on `main`.

| Branch | Purpose | Expected version shape |
| --- | --- | --- |
| `main` | Latest stable line, currently Craft 5 | `5.x.y` |
| `craft-4` | Older Craft 4 maintenance line | `4.x.y` |
| `craft-6` | Future Craft 6 stable line | `6.x.y` |
| `craft-6-alpha` | Craft 6 alpha pre-release line | `6.0.0-alpha.n` |
| `craft-6-beta` | Craft 6 beta pre-release line | `6.0.0-beta.n` |

## Workflow Expectations

The workflow must:

- run on `main`, `craft-4`, `craft-6`, `craft-6-alpha`, and `craft-6-beta`;
- pass `target-branch: ${{ github.ref_name }}` to Release Please;
- use the default config for stable branches;
- use `release-please-config.alpha.json` for `craft-6-alpha`;
- use `release-please-config.beta.json` for `craft-6-beta`;
- keep changelog headings in Craft Plugin Store format:
  `## [X.Y.Z](url) - YYYY-MM-DD`.

## Version Baselines

Each branch must have its own `.release-please-manifest.json` and
`composer.json` version baseline before testing releases. This prevents multiple
branches from trying to create the same tag.

Suggested baselines:

| Branch | Manifest/composer baseline |
| --- | --- |
| `main` | `5.0.0` |
| `craft-4` | `4.0.0` |
| `craft-6` | `6.0.0` |
| `craft-6-alpha` | `6.0.0-alpha.0` |
| `craft-6-beta` | `6.0.0-beta.0` |

## Test Commits

Use one conventional commit per branch:

| Branch | Commit | Expected Release Please result |
| --- | --- | --- |
| `main` | `fix: exercise main release line` | Stable patch release on `main`. |
| `craft-4` | `fix: exercise Craft 4 maintenance release line` | Stable patch release on `craft-4`. |
| `craft-6` | `feat: exercise Craft 6 release line` | Stable minor release on `craft-6`. |
| `craft-6-alpha` | `feat: exercise Craft 6 alpha release line` | Alpha pre-release. |
| `craft-6-beta` | `feat: exercise Craft 6 beta release line` | Beta pre-release. |

After each push, verify that Release Please opens a release PR against the same
branch that received the commit.

After merging each release PR, verify:

- the tag does not collide with another branch's tag;
- GitHub marks alpha/beta releases as pre-releases;
- `composer.json` and `.release-please-manifest.json` update together;
- `CHANGELOG.md` has the expected section and heading format.
