---
name: bluem-wordpress-svn-release
description: Prepare, validate, stage, publish, and verify a Bluem WooCommerce plugin release to WordPress.org SVN and its matching GitHub release. Use for release readiness, a new plugin version, SVN publication, or recovery from an interrupted publication.
---

# Bluem WordPress.org publication

Run this workflow from `bluem-development/bluem-woocommerce`. The WordPress.org
slug is `bluem`, the SVN repository is
`https://plugins.svn.wordpress.org/bluem`, and the source branch is `master`.
Read the current `AGENTS.md`, `README.md` release plan, `Makefile`, `.svnignore`,
`docs/ci-philosophy.md`, and `docs/dependency-security.md` before acting.
Recheck commands against those files; this skill must not freeze old versions or
dependency numbers into a future release.

## Authorization and scope

Distinguish assessment, source preparation, local staging, and publication.
Reading this skill or requesting release advice does not authorize a version
bump, release PR merge, remote tag, GitHub publication, or SVN commit. Complete
the concrete reviewable work within the user's scope before requesting any
missing authorization. Honor authorization already given in the conversation;
do not ask for it again. Keep unapproved PRs out of the release.

An explicit publication request authorizes the corresponding remote actions
once validation passes. If the scope of authorization covers only SVN, obtain
authorization for the matching GitHub release before publishing either, since
repository policy requires both. Never publish a draft/unmerged release commit.
Do not send the Makefile's release email unless separately requested.

## 1. Establish the release identity

1. Refresh Git and inspect the latest GitHub release, Git tags, SVN tags/trunk,
   and the public WordPress plugin-information API. Record differences; an
   indexing delay is not permission to reuse a tag.
2. Compare the last published source commit with current master. Identify user
   fixes separately from internal refactors and development-only updates.
   Refactors need no separate release and can accompany approved fixes.
3. Inspect candidate PRs, reviews, exact head SHAs, bases and current CI. An
   empty/pending check list is not green. For stacked PRs, wait for the approved
   parent to merge, refresh the child onto master, inspect its new diff and rerun
   checks. Merging a parent does not authorize merging its children.
4. Select a new unused version consistent with the approved changes. Record
   `release_version`, `release_tag`, the source SHA, included PRs, and runtime
   dependency versions. Discover the repository's tag naming convention.

Use non-secret variables, for example:

```bash
release_version=X.Y.Z
release_tag="$release_version"  # Confirm the existing tag convention first.
release_repo=bluem-development/bluem-woocommerce
release_svn_url=https://plugins.svn.wordpress.org/bluem
```

List remote Git/SVN tags successfully and verify the chosen tag is absent.
Do not interpret an authentication, network, or permission failure as absence.
Published GitHub and SVN tags are immutable, even when WordPress has not selected
them as stable. Never overwrite, delete, or repurpose one to retry a release.

## 2. Prepare approved source changes

Work on a clean release branch/worktree from current master. Keep all version
markers consistent:

- `bluem.php`: plugin header version and any separate runtime version marker.
- `build.env`: `PLUGIN_VERSION`.
- `readme.txt`: stable tag and factual changelog/upgrade notes.
- `languages/bluem.pot` and `languages/bluem-nl_NL.po`: Project-Id-Version;
  inspect other catalogs for version headers too. If translation content changes,
  follow the translation workflow and compile/check MO files together.
- `composer.json` / `composer.lock`: inspect the runtime dependency contract.
  Update only when explicitly in scope, using a narrow Composer update and its
  generated lock. Never perform an incidental broad dependency update.

Passing `PLUGIN_VERSION` to Make does not rewrite the PHP/readme/catalog headers.
Verify the actual files. Open a focused release PR, report tests and package
inspection, and merge only within the user's authorization. Refresh master
afterwards and pin the immutable merged `release_source_sha`.

## 3. Validate the exact source

Use PHP 8.4 or newer for Composer and PHPUnit. Run the current repository checks:

```bash
php -v
composer install --no-interaction --prefer-dist
composer validate --no-check-publish
composer check-platform-reqs
find . \( -path './vendor' -o -path './build' -o -path './docker' -o -path './svn-directory' \) -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
./vendor/bin/phpunit --configuration ./.github/workflows/phpunit.xml
composer audit --locked --no-interaction
npm ci --ignore-scripts --include=dev --no-audit --no-fund
npm audit --include=dev --audit-level=low
git diff --check
```

Require successful checks for the final source, including gateway loading,
PHP 8.4/8.5 where configured, HPOS and legacy integration, WordPress compatibility,
and the relevant settings/checkout/callback/browser acceptance coverage. Use
current CI workflows and Make targets, not old counts from a previous chat.
Do not bypass audit findings or registry failures with ignores, severity filters,
`--no-dev`, swallowed exits, or disabled checks. Audits cover the full lock;
production installation below intentionally excludes development dependencies.

Use the managed cloud runtime skill when applicable. Preserve proxy/CA settings.
Missing SVN tools, credentials, or network access are setup blockers; never
expose passwords in command arguments, logs, source files, or release notes.

## 4. Build and inspect once

Only when local package preparation is authorized, build from a clean checkout
of `release_source_sha` and retain that same reviewed package for publication:

```bash
make pre-deployment PLUGIN_VERSION="$release_version"
php scripts/check-production-dependencies.php build
```

Verify all shipped version markers and the actual installed Bluem PHP version.
Inspect the complete file list and hash manifest. Keep the top-level Composer
manifest/lock for runtime support diagnostics. Exclude development dependencies,
secrets/credentials, npm tooling, tests, scripts, Docker files, docs, agent skills
and configuration, and vendor development metadata listed in `AGENTS.md`.
Check hidden files explicitly; shell `*` does not include them. In particular,
`.agents/`, `.github/`, `.git/`, `.env`, `.svn`, and editor/cache files must not
ship. Fail on unexpected contents rather than editing the production package
by hand; fix/review the source packaging rules and rebuild if needed.

Record SHA-256 hashes of the reviewed package files outside `build/` (for
example under `/tmp`). Do not rebuild from a moving branch between inspection
and publication. Stop if source, lock, version markers, or package hashes change.

## 5. Stage a fresh SVN working copy

Use a new, dedicated checkout for each attempt. Never reuse a dirty, interrupted,
incomplete, or checksum-corrupt working copy. The following sparse layout avoids
downloading all historic tags:

```bash
# The chosen local path must not already contain another working copy.
svn checkout --depth empty "$release_svn_url" svn-directory
svn update --set-depth infinity svn-directory/trunk
svn update --depth empty svn-directory/tags
svn status svn-directory
```

Verify the remote URL, current revision and initially clean status. Recheck
remote tag absence successfully immediately before staging. Make's `add-tag`
existence probe alone is insufficient: it also treats network errors as absence.

The existing Make targets copy `build/` into the new tag and trunk. Inspect them
before use: `update-trunk` currently removes immediate children, which can
include SVN administrative metadata on some layouts. Prefer staging with rsync
below, preserving `.svn` and making both trees exact copies of the package:

```bash
test ! -e "svn-directory/tags/$release_version"
mkdir "svn-directory/tags/$release_version"
rsync -a --delete --exclude='.svn/' build/ "svn-directory/tags/$release_version/"
rsync -a --delete --exclude='.svn/' build/ svn-directory/trunk/
svn add --force "svn-directory/tags/$release_version" svn-directory/trunk
```

Inspect `svn status --xml`; schedule each intentional missing (`!`) path with
`svn delete --force -- PATH`, handling paths with spaces safely. Never blanket
delete unrelated changes or old tags. Review status, `svn diff --summarize` and
the textual diff. There must be no unknown/missing/conflicted/obstructed paths.
Only the new version tag and trunk may change; preserve root assets/other tags.
Compare both staged trees with `build/`, excluding `.svn`, including hashes and
hidden files. Present version, source SHA, package hashes, runtime dependencies,
CI evidence and SVN diff summary before requesting any missing publication
authorization. Stage all authorized preparation before that request.

## 6. Publish the approved identity

Recheck source SHA, successful checks, tag absence and staged hashes. For GitHub,
create the release/tag from the exact merged source SHA, using a reviewed
notes file rather than shell-interpolated multiline prose:

```bash
gh release create "$release_tag" --repo "$release_repo" \
  --target "$release_source_sha" --title "$release_version" \
  --notes-file /tmp/bluem-release-notes.md
```

Commit the new SVN tag and trunk together in one operation:

```bash
svn commit svn-directory -m "Release version $release_version"
```

Do not use `make release` or `make commit-to-svn` during preparation: they
publish immediately. Avoid `make send-email` unless separately authorized.
The GitHub release and SVN publication can occur close together, but must
represent the same reviewed source, version and dependency set.

## 7. Verify and recover safely

Verify the remote Git tag resolves to `release_source_sha`, the GitHub release
exists, and SVN tag/trunk have the expected version, runtime dependency and file
hashes. Use fresh `svn export` directories for remote comparisons. Confirm the
SVN revision contains both tag and trunk changes. Check the public WordPress API
and downloadable version when available; record any indexing delay separately.

If publication times out or reports an interrupted/failed commit, inspect the
remote tag, trunk, SVN log and GitHub release before retrying. A server may have
accepted the write. If an existing artifact matches exactly, resume only the
missing authorized step. If artifacts disagree, stop and report the mismatch;
never rewrite published tags. A functional rollback is a separately approved
new patch version with the desired code.

Finish with version, source SHA, included PRs, test results, package hash record,
GitHub release URL, SVN tag URL/revision, WordPress indexing status, and any
remaining blocker. Do not claim successful publication from a local build,
queued check, or successful upload alone.
