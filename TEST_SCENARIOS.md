# Copilot Commit Message Instruction Test Scenarios

Tests the rewritten `.vscode/settings.json` instructions against real diff types.

## How to use this doc

For each scenario:

1. Run the **Setup** commands in this directory
2. Stage the changes with the **Stage** command
3. In VS Code Source Control panel, click the **✨ sparkle icon** next to the commit message field — this triggers Copilot to generate a commit message from the staged diff
4. Compare Copilot's output to the **Expected** line
5. **Don't actually commit** — run the **Cleanup** command to revert and move to the next scenario

If any scenario produces a message that doesn't match the expected pattern, flag it and we'll iterate on the instructions.

Throughout this doc, `RPT` = `/Users/halin/Drive/Projects/dev/craftcms/plugins/plugins/release-please-test`.

---

## Scenario 1 — `feat:` new functionality (basic)

**Setup**

```bash
cat > src/Greeter.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }
}
PHP
```

**Stage**

```bash
git add src/Greeter.php
```

**Expected** (one of):

- `feat(helpers): add Greeter for hello-world output`
- `feat: add Greeter class for hello-world output`
- `feat(greeter): add hello-world output helper`

Any variant using `feat:` with an imperative concrete verb (`add`/`introduce`/`ship`) is acceptable.

**Cleanup**

```bash
git restore --staged src/Greeter.php && rm src/Greeter.php
```

---

## Scenario 2 — `fix:` corrects broken behavior

**Setup** (first create a Greeter with a null bug, commit it, then fix it):

```bash
cat > src/Greeter.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    public function greet(?string $name): string
    {
        return "Hello, {$name}!";  // crashes if $name is null in some PHP modes
    }
}
PHP
git add src/Greeter.php && git commit -m "chore: scaffold Greeter for test" --quiet

# Now apply the fix
cat > src/Greeter.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    public function greet(?string $name): string
    {
        $name = $name ?? 'world';
        return "Hello, {$name}!";
    }
}
PHP
```

**Stage**

```bash
git add src/Greeter.php
```

**Expected**: `fix(greeter): handle null name in Greeter::greet()` (or similar `fix:` with imperative verb)

**Cleanup**

```bash
git restore --staged src/Greeter.php
git reset --hard HEAD~1
rm -f src/Greeter.php
```

---

## Scenario 3 — `security:` vulnerability fix (CRITICAL — new type)

**Setup** (existing Greeter that doesn't escape output, then add escaping):

```bash
cat > src/Greeter.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    public function greet(string $name): string
    {
        return "<p>Hello, {$name}!</p>";
    }
}
PHP
git add src/Greeter.php && git commit -m "chore: scaffold vulnerable Greeter for test" --quiet

# Add HTML escaping
cat > src/Greeter.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    public function greet(string $name): string
    {
        $safe = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return "<p>Hello, {$safe}!</p>";
    }
}
PHP
```

**Stage**

```bash
git add src/Greeter.php
```

**Expected**: `security(greeter): escape user input to prevent XSS in greeting output`

**Critical check**: Type MUST be `security`, NOT `fix`. If Copilot outputs `fix:`, the new SECURITY TRIGGER section isn't firing — flag immediately.

**Cleanup**

```bash
git restore --staged src/Greeter.php
git reset --hard HEAD~1
rm -f src/Greeter.php
```

---

## Scenario 4 — `perf:` performance improvement (new type)

**Setup**:

```bash
cat > src/Greeter.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    public function lookupGreeting(string $locale): string
    {
        $map = ['en' => 'Hello', 'fr' => 'Bonjour', 'de' => 'Hallo'];
        return $map[$locale] ?? 'Hello';
    }
}
PHP
git add src/Greeter.php && git commit -m "chore: scaffold lookup for perf test" --quiet

# Add static cache
cat > src/Greeter.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    private static ?array $cache = null;

    public function lookupGreeting(string $locale): string
    {
        self::$cache ??= ['en' => 'Hello', 'fr' => 'Bonjour', 'de' => 'Hallo'];
        return self::$cache[$locale] ?? 'Hello';
    }
}
PHP
```

**Stage**

```bash
git add src/Greeter.php
```

**Expected**: `perf(greeter): cache greeting lookup map across calls`

**Critical check**: Type MUST be `perf`, NOT `refactor` or `feat`.

**Cleanup**

```bash
git restore --staged src/Greeter.php
git reset --hard HEAD~1
rm -f src/Greeter.php
```

---

## Scenario 5 — `revert:` rollback (new type)

**Setup** (commit something, then revert it):

```bash
echo "DEBUG=true" > src/.env-example
git add src/.env-example && git commit -m "feat: add debug env example" --quiet

# Now create a revert commit by removing it
git rm src/.env-example
```

**Stage** (already staged via `git rm`):

```bash
git status  # confirm src/.env-example is staged for deletion
```

**Expected**: `revert: roll back debug env example` or similar `revert:` form.

**Note**: Copilot may also suggest `chore:` or `refactor:` here. If it doesn't pick `revert:`, flag it — the instructions may need stronger revert triggers.

**Cleanup**

```bash
git restore --staged src/.env-example
git reset --hard HEAD~1
```

---

## Scenario 6 — `docs:` prose-only change

**Setup**:

```bash
echo "" >> README.md
echo "## Quick start" >> README.md
echo "" >> README.md
echo "Run \`composer require lindemannrock/craft-release-please-test\` to install." >> README.md
```

**Stage**

```bash
git add README.md
```

**Expected**: `docs: add quick-start install command` or `docs(quickstart): document composer install`

**Critical check**: Must be `docs:`, NOT `feat:` (Gate 3 should hold).

**Cleanup**

```bash
git restore --staged README.md
git restore README.md
```

---

## Scenario 7 — `docs:` TRAP (README describes new feature, no code change)

**Setup**:

```bash
echo "" >> README.md
echo "## Features" >> README.md
echo "" >> README.md
echo "- Multi-tenant analytics dashboard" >> README.md
echo "- Real-time XSS sanitization on user input" >> README.md
echo "- Bulk CSV import with rollback support" >> README.md
```

**Stage**

```bash
git add README.md
```

**Expected**: `docs: document analytics, sanitization, and import features` (or similar `docs:`)

**Critical check**: Must be `docs:`, NOT `feat:` or `security:`, even though the prose announces feature, security, and import work. Gate 3 must hold strictly — the actual code for those features shipped in separate commits; this is just README. If Copilot picks `feat:` or `security:`, the gate is leaking — flag immediately.

**Cleanup**

```bash
git restore --staged README.md
git restore README.md
```

---

## Scenario 8 — `i18n:` translation file (Gate 2 test)

**Setup**:

```bash
mkdir -p src/translations/es
cat > src/translations/es/release-please-test.php <<'PHP'
<?php
return [
    'Hello, {name}!' => '¡Hola, {name}!',
    'Save' => 'Guardar',
    'Cancel' => 'Cancelar',
];
PHP
```

**Stage**

```bash
git add src/translations/es/release-please-test.php
```

**Expected**: `feat(i18n): add Spanish translations for greeting and form actions` (or similar with **scope MUST be `i18n`**)

**Critical check**:

- Type: `feat:` (new locale)
- Scope: `i18n` — NOT `translations`, NOT `es`, NOT `release-please-test`
- NOT `docs:` — translations are user-facing strings

**Cleanup**

```bash
git restore --staged src/translations/es/release-please-test.php
rm -rf src/translations
```

---

## Scenario 9 — `chore(deps):` dependency bump

**Setup**:

```bash
# Modify composer.json to bump a require
python3 -c "
import json
with open('composer.json') as f:
    data = json.load(f)
data['require']['craftcms/cms'] = '^5.6.0'
with open('composer.json', 'w') as f:
    json.dump(data, f, indent=4)
"
```

**Stage**

```bash
git add composer.json
```

**Expected**: `chore(deps): bump craftcms/cms to ^5.6.0` (or `build(deps):` — either is acceptable per the taxonomy)

**Cleanup**

```bash
git restore --staged composer.json
git restore composer.json
```

---

## Scenario 10 — `ci:` workflow change (new type)

**Setup**:

```bash
# Add a comment to the workflow
sed -i '' '1i\
# Release-please workflow — manages versioning and CHANGELOG
' .github/workflows/release-please.yml
```

**Stage**

```bash
git add .github/workflows/release-please.yml
```

**Expected**: `ci: document release-please workflow purpose in header comment`

**Critical check**: Must be `ci:`, NOT `docs:` (despite being a comment-only change to a non-prose file). The file is a workflow under `.github/workflows/` — workflow changes are `ci:` per the taxonomy.

**Note**: This is a tricky case — `docs:` definition says "comment-only edits in a code file → docs". So Copilot might output `docs(ci):` or `docs:`. The cleaner choice is `ci:` because the file's PURPOSE is CI config. If Copilot says `docs:` here, we may want to add a clarifying example in the instructions.

**Cleanup**

```bash
git restore --staged .github/workflows/release-please.yml
git restore .github/workflows/release-please.yml
```

---

## Scenario 11 — Gate 4: mixed unrelated changes

**Setup**:

```bash
# Three unrelated changes at once
echo "" >> README.md && echo "## Note" >> README.md && echo "Updated docs." >> README.md

cat > src/Helper.php <<'PHP'
<?php
namespace lindemannrock\releaseplease\test;
class Helper { public static function foo(): string { return 'bar'; } }
PHP

sed -i '' 's/main/main/' .github/workflows/release-please.yml  # no-op but stages it
```

**Stage**

```bash
git add README.md src/Helper.php .github/workflows/release-please.yml
```

**Expected**: `Split into separate commits:` followed by 2-3 suggested groupings (one for the README, one for the Helper, one for the workflow). Should NOT produce a single bundled commit.

**Cleanup**

```bash
git restore --staged README.md src/Helper.php .github/workflows/release-please.yml
git restore README.md .github/workflows/release-please.yml
rm -f src/Helper.php
```

---

## Edge case scenarios (E1–E11) — for testing after the main 11

These cover real-world patterns the original 11 don't directly exercise. Run after the main 11 are passing cleanly.

### E1 — Multi-file change in same domain (3 controllers)

Tests that Gate 4 does NOT fire when changes span multiple files in one domain. Expected: a single commit message with `(controllers)` scope and a collective term in the subject (not enumerated class names).

### E2 — CSS rules + comments in same diff

Tests that `style:` is NOT misread as visual styling. Expected: `refactor(<area>):` or `feat(<area>):` reflecting the actual rule/value change.

### E3 — Build artifact + authored source mixed

Expected: `Split into separate commits: (1) feat: <source change>, (2) chore(build): rebuild dist assets`.

### E4 — Two unrelated features in same code domain

Expected: ideally `Split into separate commits:` for unrelated features. Acceptable: bundled `feat:` if features share a unifying purpose.

### E5 — New test file

Expected: `test:` type.

### E6 — Composer dep bump (banned verb regression)

Expected: `chore(deps): bump <pkg> to <version>` — must NOT contain "update".

### E7 — Pre-release `!` marker (composer.json set to 0.x.x)

Expected: commit message WITHOUT `!` suffix and WITHOUT `BREAKING CHANGE:` footer, even for breaking method renames.

### E8 — Translation key rename

Expected: `refactor(i18n):` or `fix(i18n):` — scope MUST be `i18n`.

### E9 — Comment-only edit in PHP file

Expected: `docs(<class>): expand <Class> docblock` or `docs: document <Class> with @since`. Must NOT contain "update".

### E10 — Multiple translation files staged together (multi-locale)

Realistic case: dev adds a feature with translatable strings, updates all 12 language files (EN, DE, FR, NL, ES, AR, IT, PT, JA, SV, DA, NO). All staged together.

**Setup**:
```bash
for lang in en de fr nl es ar it pt ja sv da no; do
  mkdir -p src/translations/$lang
  cat > src/translations/$lang/release-please-test.php <<PHP
<?php
return ['Hello' => 'Hello'];
PHP
done
git add src/translations
```

**Expected**: a single commit message with `(i18n)` scope, e.g.:
- `feat(i18n): add Hello translation across all 12 languages`
- `feat(i18n): seed Hello key in all locales`

**Critical**:
- Type: `feat:` (new keys/locales)
- Scope: `(i18n)` mandatory per Gate 2 — NOT `(translations)`, NOT locale codes, NOT enumerating
- Should NOT split into 12 separate commits (same domain — Gate 4 doesn't fire)

**Cleanup**: `git restore --staged src/translations && rm -rf src/translations`

### E11 — Multiple new build/dist files staged together

Realistic case: dev runs `npm run build` after a source change, regenerating multiple hashed bundles + manifest.

**Setup**:
```bash
mkdir -p web/dist/assets
cat > web/dist/assets/widget-A1b2C3d4.js <<'JS'
(function(){})();
JS
cat > web/dist/assets/vendor-X9y8Z7w6.js <<'JS'
(function(){})();
JS
cat > web/dist/assets/admin-P5q4R3s2.js <<'JS'
(function(){})();
JS
cat > web/dist/assets/manifest.json <<'JSON'
{"src/widget.js":{"file":"assets/widget-A1b2C3d4.js"}}
JSON
git add web/dist
```

**Expected**: `chore(build): rebuild dist assets` — single commit per Gate 1, regardless of how many bundle files.

**Critical**:
- Type: `chore:` with `(build)` scope
- Should NOT enumerate filenames
- Should NOT read bundle contents and classify as `feat:`

**Cleanup**: `git restore --staged web/dist && rm -rf web/dist`

### E12 — Packaging config edit (`.gitattributes`, `.gitignore`, etc.) — should be chore, not feat

Real-world failure observed: VS Code Copilot suggested `feat: add export-ignore for GitHub and dev tooling directories` for a `.gitattributes` edit that added `.github/` and `.githooks/` to export-ignore. That's wrong — `.gitattributes` changes are packaging housekeeping, never user-facing functionality.

**Setup**:

```bash
cat >> .gitattributes <<'EOF'

# GitHub/dev tooling
.github/ export-ignore
.githooks/ export-ignore
EOF
```

**Stage**

```bash
git add .gitattributes
```

**Expected** (any of these is acceptable):

- `chore: exclude .github/ and .githooks/ from composer archives`
- `build: exclude .github/ and .githooks/ from composer archives`
- `chore(build): add .github/ and .githooks/ to export-ignore list`

**Critical**:

- Type MUST be `chore:` or `build:` — NEVER `feat:`
- Subject should describe the OUTCOME (exclude from archive) not the editing activity (add lines)
- Same rule applies to edits in `.gitignore`, `.editorconfig`, `.npmrc`, `.dockerignore`, `.gitattributes`, and similar packaging/dev-tooling config files

**Failure modes**:

- ❌ `feat: add export-ignore for X` — packaging config never produces user-facing functionality
- ❌ `feat: add new export-ignore entries` — same issue, "new" doesn't make it `feat:`
- ❌ Subject phrased as "add export-ignore for X" — describes editing, not outcome

**Cleanup**: `git restore --staged .gitattributes && git restore .gitattributes`

**Iteration note for instructions**: v3.1+ should add packaging config files (`.gitattributes`, `.gitignore`, `.editorconfig`, etc.) to the DECISION TREE / TYPE TAXONOMY so Copilot routes them to `chore:` / `build:` automatically. Probably belongs near the BANNED VERBS section or as a new "Packaging config files" trigger.

### E13 — Identical changes across plugins should produce identical (or near-identical) commit messages

Real-world failure observed: the same `.githooks/pre-commit` change (replacing hardcoded `/tmp/phpstan-output.txt` with `mktemp` + `trap` cleanup) was applied across 4 plugins, but VS Code Copilot generated 4 different commit messages:

```
fix: correct PHPStan output handling in pre-commit hook
refactor(pre-commit): streamline PHPStan output handling in pre-commit hook
refactor: streamline PHPStan output handling in pre-commit hook
refactor: streamline PHPStan output handling in pre-commit hook
```

Two distinct problems:

1. **Wrong type on one variant**: `fix:` is incorrect — nothing was broken. Replacing hardcoded paths with mktemp is a `refactor:` (internal restructure, no behavior change). The hook still runs PHPStan, still writes output to a temp file, still cleans up — just more robustly.
2. **Inconsistency across plugins**: identical diffs should produce identical (or near-identical) commit messages. Drift makes git history harder to scan.

**Setup** (simulate the actual diff that prompted those messages):

```bash
cat > .githooks/pre-commit <<'OLD'
#!/bin/bash
"$PHPSTAN" analyse --configuration=phpstan.neon > /tmp/phpstan-output.txt 2>&1
if ! grep -q "OK" /tmp/phpstan-output.txt; then
    cat /tmp/phpstan-output.txt
    rm /tmp/phpstan-output.txt
    exit 1
fi
rm /tmp/phpstan-output.txt
OLD
git add .githooks/pre-commit && git commit -m "chore: scaffold old hook" --quiet

cat > .githooks/pre-commit <<'NEW'
#!/bin/bash
PHPSTAN_OUTPUT=$(mktemp "${TMPDIR:-/tmp}/phpstan-output.XXXXXX")
trap 'rm -f "$PHPSTAN_OUTPUT"' EXIT
"$PHPSTAN" analyse --configuration=phpstan.neon > "$PHPSTAN_OUTPUT" 2>&1
if ! grep -q "OK" "$PHPSTAN_OUTPUT"; then
    cat "$PHPSTAN_OUTPUT"
    exit 1
fi
NEW
```

**Stage**: `git add .githooks/pre-commit`

**Expected**: `refactor:` (NOT `fix:`). E.g.:

- `refactor(pre-commit): use mktemp + trap for PHPStan output cleanup`
- `refactor(githooks): replace hardcoded temp path with mktemp + trap`

**Critical**:
- Type MUST be `refactor:` — internal restructure, no behavior change
- NEVER `fix:` — nothing was broken
- Subject should mention the CONCRETE change (mktemp, trap), not vague verbs like "streamline" or "improve"

**Failure modes**:
- ❌ `fix: correct PHPStan output handling` — wrong type, vague verb "correct"
- ❌ `refactor: streamline X` — "streamline" is borderline-vague (acceptable but "use mktemp + trap" is more concrete)

**Iteration note for instructions**: when the same diff is staged across multiple plugin repos, Copilot's output should be deterministic. The current instructions don't explicitly guarantee determinism — adding language like "for identical diffs across repos, the same type and structure must be produced" could help. Also reinforce that **refactor** is the default for "make existing code more robust without behavior change" — `fix` is reserved for actually-broken behavior.

### E14 — `.vscode/settings.json` edits — hallucinated content unrelated to diff

Real-world failure observed: when committing changes to `.vscode/settings.json` (specifically the Copilot commit-message instructions block), Copilot generated:

```
Split into separate commits: (1) feat(i18n): add new translation keys for user notifications, (2) docs: document new user notification translations
```

The actual diff had **nothing to do with translations or user notifications**. Copilot confabulated content that wasn't in the diff — possibly inferring from the filename "settings.json" that it's an app settings file, or context bleed from prior diffs in the session.

**Setup** (simulate an edit to the Copilot instructions inside settings.json):

```bash
# Append a line to the github.copilot.chat.commitMessageGeneration.instructions array
# (any change to settings.json content — could be a tooling config change, prettier rule, etc.)
echo "  // dev note" >> .vscode/settings.json
git add .vscode/settings.json
```

**Expected** — type `chore:` (dev-tooling config), describe the actual diff:

- `chore(vscode): tighten Copilot commit instructions for packaging configs`
- `chore: add new section to Copilot commit instructions`
- `chore(editor): adjust VS Code settings for X`

**Critical failure modes**:

- ❌ `feat(i18n): add new translation keys for user notifications` — confabulated content not in diff (HALLUCINATION)
- ❌ Any subject referencing content not in the actual diff
- ❌ `feat:` or `docs:` for a `.vscode/` config edit (should be `chore:`)

**Iteration note**: the SCOPE HINTS table should explicitly route `.vscode/**` edits to `chore:`. The DECISION TREE / HARD GATES should add a "Read the actual diff content" reminder — Copilot shouldn't generate content based on filename inference alone. Possibly a new gate before classification: "If the subject describes content that isn't in the diff, STOP and re-read the diff."

### E15 — Multiple files in same directory (e.g., issue templates) should NOT split

Real-world failure observed: adding 3 GitHub issue templates (bug-report.yml, feature-request.yml, question.yml) under `.github/ISSUE_TEMPLATE/` in a single staging — Copilot suggested:

```
Split into separate commits: (1) feat: add bug report issue template, (2) feat: add feature request issue template, (3) feat: add question issue template
```

This is Gate 4 over-firing. All 3 files are in the same directory, same domain (workflow/dev-tooling), serving the SAME coherent purpose (setting up issue templates for the repo). Should be ONE commit.

**Setup**:

```bash
mkdir -p .github/ISSUE_TEMPLATE
cat > .github/ISSUE_TEMPLATE/bug-report.yml <<'YML'
name: Bug Report
about: Report a bug
YML
cat > .github/ISSUE_TEMPLATE/feature-request.yml <<'YML'
name: Feature Request
about: Request a feature
YML
cat > .github/ISSUE_TEMPLATE/question.yml <<'YML'
name: Question
about: Ask a question
YML
git add .github/ISSUE_TEMPLATE
```

**Expected** — single commit, NOT split:

- `chore: add issue templates (bug report, feature request, question)`
- `ci: add GitHub issue templates`
- `chore(github): scaffold issue templates`

**Critical**:
- Type `chore:` or `ci:` (NOT `feat:` — issue templates aren't user-facing functionality)
- SINGLE commit (Gate 4 STEP 2: 1 domain → 1 commit)
- Same-purpose multi-file additions to the same directory don't split

**Failure modes**:

- ❌ `Split into separate commits:` for files in the same directory (Gate 4 over-fire)
- ❌ `feat: add ... template` — issue templates aren't user-facing features
- ❌ Enumerating each file as a separate commit when they all serve one purpose

**Iteration note**: Gate 4 STEP 2 should add: "Multiple files in the same directory serving the same purpose are NOT a split signal — they're a single coherent change (e.g., issue templates, translation files for one feature, all CSS rules for one component)."

### E16 — Multi-locale translation corrections should NOT split by locale

Real-world failure observed (twice, June 2026): staging corrections across several locale files produced a per-locale split — despite Gate 4 STEP 2 explicitly stating 12 translation files = 1 domain = 1 commit:

```
Split into separate commits: (1) feat(i18n): correct French translations for import/export permissions and messages, (2) feat(i18n): update Italian translations for column mapping, (3) feat(i18n): refine Japanese translation for CSV delimiter, (4) fix(i18n): improve Dutch translations for log clearing and column mapping, (5) fix(i18n): enhance Norwegian translations for log messages, (6) fix(i18n): adjust Portuguese translations for import history, (7) fix(i18n): clarify Swedish translation for pipe character
```

```
Split into separate commits: (1) fix(i18n): correct Arabic translation for date range, (2) fix(i18n): correct Danish SMS log translations, (3) fix(i18n): correct German SMS part terminology, (4) fix(i18n): correct Spanish provider activation message, (5) fix(i18n): correct French analytics provider activation message, (6) fix(i18n): correct Italian log deletion messages, (7) fix(i18n): correct Portuguese provider and sender ID messages, (8) fix(i18n): correct Swedish SMS sending statistics description
```

Four distinct problems:

1. **Per-locale split**: one domain (translation) must be ONE commit, regardless of how many locales or feature areas the strings touch.
2. **Type-mixing as a split trigger**: the first output mixes `feat(i18n)` and `fix(i18n)` items — likely why Copilot split. The rule is: corrections only → `fix`; any new keys mixed in → single `feat` (most impactful wins). Never split by type.
3. **Misclassified corrections**: items 1-3 of the first output are corrections to existing strings typed as `feat` — Gate 2 says corrections = `fix`.
4. **Banned verbs inside split items**: "update", "improve", "enhance", "refine", "adjust" — the PRE-FLIGHT CHECK wasn't applied to wrapper items.

**Setup** (corrections to existing strings in 3+ locale files):

```bash
# Assumes src/translations/{fr,it,ja}/<handle>.php exist with a 'Pipe' key
sed -i '' "s/'Pipe' => '.*'/'Pipe' => 'Barre verticale'/" src/translations/fr/*.php
sed -i '' "s/'Pipe' => '.*'/'Pipe' => 'Barra verticale'/" src/translations/it/*.php
sed -i '' "s/'Pipe' => '.*'/'Pipe' => 'パイプ'/" src/translations/ja/*.php
git add src/translations
```

**Expected** — single commit, NOT split, locale detail in body:

```
fix(i18n): correct translations across 3 locales

- correct French translation for pipe character
- correct Italian translation for pipe character
- correct Japanese translation for pipe character
```

**Critical**:
- SINGLE commit — never the `Split into separate commits:` wrapper for a translation-only staging
- Type `fix` (corrections to existing strings); if the staging also added new keys → `feat` for the whole commit
- No banned verbs anywhere, including inside any wrapper items
- Body bullets carry per-locale detail (body is git-history-only; the changelog shows the subject)

**Failure modes**:
- ❌ `Split into separate commits: (1) fix(i18n): correct French..., (2) fix(i18n): correct Italian..., ...` — per-locale split (Gate 2/Gate 4 over-fire)
- ❌ Mixed `feat(i18n)` + `fix(i18n)` items for the same staging — type-mixing used as a split signal
- ❌ `feat(i18n)` for pure corrections — Gate 2 type leak
- ❌ "update/improve/enhance/refine/adjust" in any subject or split item — banned verbs

**Cleanup**: `git restore --staged src/translations && git restore src/translations`

**Iteration note**: addressed in v3.1 instructions (2026-06-12) — Gate 2 "translation staging is ALWAYS ONE commit" rule with this exact wrong/right pair, Gate 4 STEP 1.5 cohesion check, PRE-FLIGHT check 5 (gates apply to split items), and "refine"/"adjust" added to banned verbs. Re-run this scenario to verify compliance.

### E17 — A whole docs folder should NOT split by page or asset type

Real-world failure observed (June 2026): staging a complete docs folder for a plugin produced a per-page + per-asset split — despite Gate 3 (all-docs → `docs:`) and Gate 4 STEP 2 (one domain → one commit):

```
Split into separate commits: (1) docs: add installation guide for SMS Manager, (2) docs: add quickstart guide for SMS Manager, (3) docs: add requirements documentation for SMS Manager, (4) docs: add translations documentation for SMS Manager, (5) docs: add troubleshooting guide for SMS Manager, (6) docs: add index.json and plugin.json for SMS Manager, (7) docs: add images for SMS Manager documentation
```

Three distinct problems:

1. **Per-page split**: items 1-5 are all `.md` files under `docs/**` — one domain, must be ONE commit. Distinct doc pages are NOT a split signal (same class as E15's same-purpose rule).
2. **Asset types counted as separate domains**: items 6 (`index.json`/`plugin.json` docs-manager metadata) and 7 (images) were peeled off because the original "prose" domain was defined as `.md/.mdx/.txt` only — json and images under `docs/**` fell outside it. Everything under `docs/**` is the docs domain, regardless of extension.
3. **Plugin name repeated in every subject**: "for SMS Manager" is redundant — single-plugin repo, the repo IS the plugin (same reason the plugin name is banned as a scope).

**Setup** (a new docs folder with guides, metadata, and an image):

```bash
mkdir -p docs/get-started docs/resources docs/images
echo "# Installation" > docs/get-started/installation.md
echo "# Quickstart" > docs/get-started/quickstart.md
echo "# Requirements" > docs/get-started/requirements.md
echo "# Translations" > docs/resources/translations.md
echo "# Troubleshooting" > docs/resources/troubleshooting.md
echo '{"title":"Docs"}' > docs/index.json
echo '{"handle":"sms-manager"}' > docs/plugin.json
printf 'PNG' > docs/images/overview.png
git add docs
```

**Expected** — single commit, NOT split, per-page detail in body:

```
docs: add plugin documentation

- add installation, quickstart, and requirements guides
- add translations and troubleshooting pages
- add docs-manager index.json and plugin.json
- add overview image
```

**Critical**:
- SINGLE commit — never the `Split into separate commits:` wrapper for a docs-only staging
- Type `docs:` (everything under `docs/**`, including json metadata and images)
- No plugin name in the subject ("for SMS Manager" is redundant)
- Body bullets carry per-page detail

**Failure modes**:
- ❌ `Split into separate commits: (1) docs: add installation guide, (2) docs: add quickstart guide, ...` — per-page split (Gate 3/Gate 4 over-fire)
- ❌ Peeling `index.json`/`plugin.json` or images into their own commits — docs assets are the docs domain
- ❌ "for SMS Manager" / plugin name repeated in subjects

**Cleanup**: `git restore --staged docs && rm -rf docs`

**Iteration note**: addressed in v3.1 instructions (2026-06-12) — Gate 3 broadened to "anything under docs/** is the docs domain regardless of extension" + "docs-only staging is ALWAYS ONE commit" rule with this exact wrong/right pair, Gate 4 STEP 1 prose/docs domain extended to docs-folder assets, and a docs-folder example added to the DOMAIN COUNT == 1 list. Re-run to verify compliance.

---

## Reporting results

After running all 11 scenarios, list any that failed (Copilot output didn't match expected pattern). Common failure modes:

- Wrong type (e.g., `fix:` instead of `security:` in scenario 3) → need stronger trigger language
- Wrong scope (e.g., `translations` instead of `i18n` in scenario 8) → gate 2 leaking
- Gate breach (e.g., `feat:` for pure-README change in scenario 7) → gate 3 leaking
- Vague verb (e.g., "update", "improve") → subject rules not enforced
- Bundling in scenario 11 → gate 4 leaking

We iterate on the settings.json text based on what fails.

## Out of scope for this test (handled separately)

- CHANGELOG-level features: GitHub alerts (`> [!NOTE]`, `> [!WARNING]`), `[CRITICAL]` suffix on version headings, reference-style links. Those affect what release-please writes to CHANGELOG.md from commit BODIES, not how Copilot generates commit messages. After commit-message tests pass, we'll add a separate doc + workflow step for those.
