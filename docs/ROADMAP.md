# NoelClark.com Roadmap

## Version 1.0

### Feature 01 — Arrival

**Governing Version 1.0 landing architecture** — **`NOELCLARK_PRODUCT_MODEL.md` §14** (see **`PROJECT_CONSTITUTION.md`** `# Amendment Process`; supersedes earlier mandatory Memory Field sequence):

PLANT YOUR FLAG

Opening black field / white typography.

↓

Social-media remembered-world experience

↓

Manifesto

Concludes with the approved declaration, including **“I'm planting my flag.”** (displayed as **I'M PLANTING MY FLAG.**)

↓

ENTER NOELCLARK.COM

↓

NoelClark.com Homepage

Crossing remains the approved transition into Manifesto; see `docs/DECISIONS.md`.

The entrance is intentionally **monogram-free**: after **I'M PLANTING MY FLAG.** the visitor sees **ENTER NOELCLARK.COM**, a visitor-controlled accessible action that leads directly to the NoelClark.com Homepage (commit `97cab27`). No entrance monogram is present, required, or pending; monogram image assets may remain in the repository but are not part of the entrance. Portal is not a mandatory Version 1 passage. There is no second/closing **PLANT YOUR FLAG**. Wander is not the mandatory immediate destination. Mail Room and P.S. are Homepage choices, not mandatory landing chapters.

**Status:** Landing, entrance, and Homepage are **implemented**. Homepage entry/session behavior is implemented: returning Home within the same browsing session restores the Homepage directly instead of replaying PLANT YOUR FLAG (commit `bd84cec`).

**Authority note:** `NOELCLARK_PRODUCT_MODEL.md` §14 (APPROVED — LOCKED) still describes a final colorful NC monogram as the clickable threshold. That locked text has not been amended to the monogram-free entrance; see `PROJECT_HANDOFF_v2.0.md` §3.

**Geography of Curiosity** remains a preserved project concept. Its **Version 1.0 placement is UNRESOLVED**.

---

### HISTORICAL — SUPERSEDED BY `NOELCLARK_PRODUCT_MODEL.md` §14

The following earlier mandatory visitor spine is preserved for audit reference only. It does **not** govern current Version 1.0 implementation:

PLANT YOUR FLAG

Opening black field / white typography.

↓

Borrowed Land

↓

Static Social Post

Circular invitation: visitor activation (immediate) or approved idle continuation (observation/read interval — exact timing unresolved).

↓

Crossing

Social interface surrender / fade. Leads directly into Manifesto.

↓

Manifesto

Concludes with the approved declaration, including **“I'm planting my flag.”**

↓

Portal

NC watercolor monogram threshold. Story Ring is not Portal.

↓

Emergence

Geography of Curiosity. Night becomes day; full illumination completes into an entirely white field.

↓

Closing PLANT YOUR FLAG

Black typography on the white field. Distinct from the opening PLANT YOUR FLAG.

↓

Wander

Free exploration of NoelClark.com. Does not automatically require another cinematic scene.

---

**Retired from mandatory Feature 01 progression** (archival only; may not gate Manifesto):

Memory Field · Journal · Wayback Memory · Typewriter · Memory Cascade · Expanding Room · separate Planting My Flag state

---

### Feature 08 — Returning Visitor Experience

* Detect previous visit.
* Navigation is globally available from initial arrival (per `PORTAL_TREATMENT.md` Part Two); returning-visitor entry mechanics remain separately governed.
* Offer “Enter Home.”
* Offer “Experience the Journey.”
* Preserve the complete first-time experience.

### Production Requirement — Landing Page Social Metrics

Like and Save are the only homepage social interactions in Version 1.0. Comments are removed from Version 1.0: no Comment control, count, composer, guestbook, COMMENTS page, comment storage, moderation, or comment notifications.

Like and Save are global, cumulative totals shared across all visitors, browsers, sessions, and devices.

Each reaction may succeed at most once per page visit, and each success permanently adds 1 to its total. After a successful Like the heart stays red, and after a successful Save the bookmark stays gold, for the rest of that page visit; further clicks on that reaction during the visit do nothing and send no additional request. A failed request rolls back and may be retried. A genuine reload or new page visit resets eligibility, so the visitor may Like and Save again. Back/forward cache restoration may preserve the same in-memory visit state. There is no Unlike or Unsave. There are no cookies, localStorage, sessionStorage, visitor IDs, IP tracking, fingerprinting, or other persistent visitor identity.

Totals are persisted in WordPress (two non-autoloaded `wp_options` rows, incremented atomically through the `noelclark/v1/social` REST routes). Production uses this existing REST implementation and these `wp_options` totals. This replaces the former localStorage placeholder; localStorage is never a counter source.

Production WP Engine has a platform-level cache exclusion for `/wp-json/noelclark/v1/social`. It is required so live global totals are never served stale. Production verification on 2026-10-12/13 returned `cache-control: no-store`, `x-pass-why: custom-path`, and `cf-cache-status: BYPASS`. Removing this Production cache exclusion is a regression risk. The exclusion is Production-specific. Production persistence of totals after a hard refresh has passed.

The approved landing-page animation, pacing, dissolve, and Crossing behavior are unchanged. The existing Like and Save dissolve timings are preserved exactly.
