# Multibanner

Drupal contrib module. The repo root **is** the module; `web/` is a generated
Drupal install with the module symlinked into `web/modules/custom/multibanner`.

## Commands

`ddev phpcs`, `ddev phpcbf`, `ddev phpstan`, `ddev phpunit`.

## Generated files

`/web`, `/vendor`, `/recipes`, `/phpcs.xml.dist` and `/phpstan*` are gitignored
and produced by ddev-drupal-contrib (`ddev poser`) from Drupal's
gitlab_templates. This is the same source Drupal CI uses, so local and CI agree
by construction. Don't edit them expecting the change to persist or to reach CI.

## Version support

`core_version_requirement: ^10.3 || ^11`. Both must keep working.

- `multibanner.theme.inc` is a procedural back-compat shim for D10; its OOP
  counterpart is `src/Hook/ThemeHooks.php`.
- Don't hard-require D11-only classes. Fetch them lazily
  (`ThemeHooks::preprocessViewsViewMultibanner`) and avoid overriding
  constructors whose core signature has changed across versions — prefer
  `parent::create()` plus property assignment
  (`MultibannerThumbnailFormatter::create()`).

## Gotchas

- **`->entity` is nullable.** Every null-deref bug found in this module so far
  was a `->entity` dereferenced without a guard. Type plugins should return
  `FALSE` instead, per `MultibannerTypeInterface::getField()`'s contract.
- **A non-nullable `@var` annotation suppresses phpstan's null check.** Putting
  one on an `->entity` hides exactly the bug it appears to document. Narrowing a
  *parameter* to a concrete type is fine; narrowing `->entity` to non-null is
  not.
- **Entity properties get serialized.** `ContentEntityBase` inherits
  `DependencySerializationTrait::__sleep()`, which is `get_object_vars($this)`,
  so every declared property on `Multibanner` lands in cached payloads.
  Renaming one is a BC break: payloads written by older releases unserialize
  into dynamic properties (PHP 8.2 deprecation) until caches are rebuilt.

## Conventions

- Conventional Commits, no co-author trailer.
- phpcs ruleset is `Drupal` only (DrupalPractice is commented out). Its line
  length rule applies to comments, not code.

## Known issues

Deliberately unfixed:

- `VideoEmbedField::getDefaultName()` falls back to
  `parent::getDefaultThumbnail()`, returning a thumbnail URI where a name
  belongs. Looks like copy-paste, but fixing it changes behaviour.
- `Multibanner::getMultibannerBundle()` dereferences a nullable bundle entity.
  Pre-existing, mirrors core `Media::getSource()`, and has no sensible fallback.

phpstan is clean at level 5. Level 6 adds ~139 missing-type docblock findings
across ~30 files. Levels 8-9 are mostly `mixed` propagation from untyped Drupal
APIs, but level 8 is worth re-running occasionally — it surfaced three real
null/false bugs that level 5 does not catch.
