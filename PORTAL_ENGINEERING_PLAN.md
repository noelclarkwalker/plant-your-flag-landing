# PORTAL_ENGINEERING_PLAN.md

> **JURISDICTION — PRESERVED PORTAL ENGINEERING REFERENCE (NOT CURRENT MANDATORY V1 LANDING BUILD AUTHORITY)**
>
> This document is **preserved Portal engineering/implementation reference** for Portal storyboard shots 001–006 and related Portal state/threshold planning. It is **NOT** current mandatory Version 1.0 landing build authority.
>
> Portal → Emergence handoff material below represents **preserved engineering planning** whose former mandatory Version 1.0 placement is **superseded**.
>
> **Current Version 1.0 landing routing:** `NOELCLARK_PRODUCT_MODEL.md` §14 — PLANT YOUR FLAG → social-media remembered-world experience → Manifesto → final colorful NC monogram → visitor action/click → NoelClark.com Homepage. This annotation does **not** prescribe the technical monogram routing mechanism.
>
> **Current remaining landing requirement:** final NC monogram action/click → Homepage, as tracked by `docs/FEATURE_PROGRESS.md`.
>
> Portal shot/state/threshold engineering details below remain preserved for **future Portal work**. Do **not** interpret this document as authorization to implement Portal or Emergence as current required Version 1.0 landing work.
>
> **Geography of Curiosity** remains preserved. Its **Version 1.0 placement is UNRESOLVED**.

**Version:** 1.2  
**Status:** Engineering translation — not implementation authority  
**Scope:** Portal storyboard shots 001–006, aligned with approved NC monogram Portal architecture  

---

## Architectural Alignment (v1.2)

**Authority:** `docs/DECISIONS.md` — Continuous Experience Architecture (Version 1.0); `PORTAL_TREATMENT.md` Parts One–Three; `PORTAL_STORYBOARD.md` (approved).

This plan translates the approved monogram Portal architecture into engineering responsibilities. Approved visuals remain intact. Flow is continuous scroll with no page transitions.

### Continuous scroll model

```
Hero
  ↓  scroll
Remembered social world (Story Ring — not a separate destination)
  ↓  scroll (after social dissolve trigger)
Approved Manifesto (production baseline — appearance unchanged)
  ↓  scroll
NC monogram → Portal
  ↓
Emergence
```

- **No page transitions** anywhere in this sequence.
- Manifesto is **not rebuilt** — same typography, copy, black field, hierarchy, composition.
- Social dissolve removes **interface chrome only** — words remain.
- **Portal symbol (permanent):** `assets/images/nc-monogram-watercolor.png` — exact asset; no recreation, redraw, restyle, or substitution.
- **Period** after *"I'm planting my flag."* remains ordinary manifesto punctuation — **not** Portal.
- **Story Ring** is continuous social world — **not** Portal symbol or crossing target.

### Resolved architectural decisions (engineering baseline)

| # | Decision | Authority |
|---|---|---|
| 1 | NC watercolor monogram is the Portal symbol | `PORTAL_TREATMENT.md`, `docs/DECISIONS.md` |
| 2 | Period is punctuation only — not Portal | `PORTAL_TREATMENT.md`, `docs/DECISIONS.md` |
| 3 | Story Ring is social world — not Portal | `docs/DECISIONS.md`, `PORTAL_STORYBOARD.md` |
| 4 | Crossing interaction target is the NC monogram | `PORTAL_STORYBOARD.md` Shot 004, `PORTAL_TREATMENT.md` |
| 5 | Navigation persistent from initial arrival | `PORTAL_TREATMENT.md` Part Two, `docs/DECISIONS.md` |
| 6 | Portal ends at Shot 006 — Threshold; Geography revealed by Emergence only | `PORTAL_STORYBOARD.md`, `PORTAL_TREATMENT.md` |
| 7 | Flag decisions superseded for Portal entry — monogram is threshold | `docs/DECISIONS.md` Portal section |

**Engineering note:** `PORTAL_STORYBOARD.md` shots 001–006 are authored for the NC monogram threshold. This plan implements those shots. Where creative authority is deliberately unresolved (`PORTAL_TREATMENT.md` UNRESOLVED), engineering stops — it does not infer.

---

## 1. Purpose and Authority

This document translates approved creative authorities into an engineering plan for the Portal cinematic sequence defined in `PORTAL_STORYBOARD.md`.

It defines engineering responsibilities per shot. It does not define visitor experience. It does not create creative decisions.

### Governing documents (read order)

| Order | Document | Path | Role |
|---|---|---|---|
| 1 | Master Project Brief | `docs/MASTER_PROJECT_BRIEF.md` | Product vision, Portal = passage, Emergence = arrival |
| 2 | Feature 01 Treatment | `docs/     FEATURE_01_TREATMENT.md` | Narrative authority for Arrival through Wander |
| 3 | World of Curiosity | `WORLD_OF_CURIOSITY.md` | Geography laws — **withheld during Portal** |
| 4 | Portal Treatment | `PORTAL_TREATMENT.md` | Portal scope + permanent Navigation Philosophy (Part Two complete) |
| 5 | Portal Storyboard | `PORTAL_STORYBOARD.md` | Shot-by-shot cinematic authority for Portal |

Supporting permanent decisions: `docs/DECISIONS.md`, `docs/ROADMAP.md`, `FEATURE_01_ENGINEERING_PLAN.md` (state boundaries).

### Permanent boundaries (not negotiable in engineering)

- **Portal owns passage.** Emergence owns arrival.
- **Portal ends at Shot 006 — Threshold.** Geography is never revealed by Portal.
- **Emergence** first reveals the Geography of Curiosity.
- **The watercolor NC monogram is the Portal.** The period is not.
- Engineering may not redesign shots, invent UI, or substitute creative intent with convenient implementation.

### Scope of this plan

| In scope | Out of scope |
|---|---|
| Shots 001–006 | Emergence shots |
| Engineering handoff Portal → Emergence | Geography implementation |
| Navigation presence rules from `PORTAL_TREATMENT.md` Part Two | Flag beat details (superseded for Portal entry) |
| Pre-condition entry from Manifesto conclusion | Return-visitor Feature 08 |

---

## 2. Entry Preconditions

Portal engineering begins when the Manifesto conclusion and monogram threshold preconditions are satisfied (`docs/ROADMAP.md`, `docs/DECISIONS.md`, `PORTAL_TREATMENT.md` Part Three).

### Required beginning state (Shot 001 entry)

| Condition | Authority |
|---|---|
| Visitor has scrolled through Hero and remembered social world | Continuous Experience Architecture |
| Social interface dissolve complete — words remain, chrome gone | `docs/DECISIONS.md` |
| Approved manifesto visible — **production baseline appearance unchanged** | Continuous Experience Architecture |
| Manifesto copy verbatim — continuous bridge + declaration ending in final declaration | Approved copy — `MANIFESTO_TREATMENT.md` |
| Final declaration *"I'm planting my flag."* visible — period remains ordinary punctuation | `docs/DECISIONS.md`, `PORTAL_TREATMENT.md` |
| NC monogram follows manifesto as final encountered object | `docs/DECISIONS.md`, `PORTAL_TREATMENT.md` Part Three |
| Monogram uses exact asset `assets/images/nc-monogram-watercolor.png` | `docs/DECISIONS.md` |
| Emergence / present-day home not yet authoritative | `docs/DECISIONS.md` |

### Symbolic objects in play

| Object | Approved role |
|---|---|
| NC monogram (`assets/images/nc-monogram-watercolor.png`) | Portal symbol — signature becomes Portal |
| Story Ring | Continuous social world — not Portal destination or symbol |
| Final period | Manifesto punctuation only — not Portal interaction target |
| Approved manifesto | Production baseline — scroll position changes only |

---

## 3. Global Architecture

### 3.1 Portal state model

```
Hero + social world (continuous scroll)
        ↓
Social dissolve → approved Manifesto revealed (appearance unchanged)
        ↓
Visitor reads manifesto → encounters NC monogram beneath declaration
        ↓
[Shot 001] Stillness
        ↓
[Shot 002] Recognition
        ↓
[Shot 003] Impossible Depth
        ↓
[Shot 004] Crossing
        ↓
[Shot 005] Between
        ↓
[Shot 006] Threshold
        ↓
Emergence (separate engineering plan)
```

### 3.2 Authoritative owners

| Domain | Owner |
|---|---|
| Portal shot progression | Portal module (new — not yet in production) |
| Manifesto / declaration presentation | cinema-manifesto (production baseline — unchanged) |
| Portal symbol / threshold | NC monogram — `assets/images/nc-monogram-watercolor.png` |
| Social dissolve | Arrival module — chrome only, words preserved |
| Continuous scroll orchestration | Experience shell — no page transitions |
| Passage (Between, Threshold) | Portal module |
| Geography reveal | Emergence — **not Portal** |
| Navigation markup and presence | Navigation module — governed by `PORTAL_TREATMENT.md` Part Two |
| Story Ring identity continuity | Arrival / persistent circular element |

### 3.3 Global engineering rules

1. **No attention-seeking UI** at threshold — no buttons, arrows, chevrons, progress bars, instructional copy, glow, pulse, or conventional portal affordances on the monogram (`PORTAL_TREATMENT.md` Attention, Things Portal Must Never Become; narrative in `FEATURE_01_TREATMENT.md`).
2. **Visitor moves toward Portal.** Portal does not move toward visitor (`docs/DECISIONS.md`).
3. **Crossing is consent.** No auto-pull, no forced progression (Shot 004).
4. **Silence is material** (`WORLD_OF_CURIOSITY.md`, Shot 001).
5. **Manifesto environmental tension** (`js/manifesto-tension.js`) must **cease or recede** before Shot 001 stillness — motion during "nothing moves" violates storyboard.
6. **Navigation** per Part Two: globally available from initial arrival; present during Manifesto, Threshold, Portal — must not compete with cinematic beats.
7. **Monogram identity preserved** — the mark must remain recognizably the author's watercolor signature throughout Recognition and depth (`PORTAL_TREATMENT.md` Locked Creative Laws 3–4). Engineering must not substitute, recreate, or restyle the approved asset.
8. **Period untouched** — manifesto final line period remains inline punctuation; Portal engineering does not attach depth, interaction, or animation to the period.

### 3.4 Approved engineering boundaries and remaining creative questions

- **Portal Treatment UNRESOLVED list**
  - **Authority:** `PORTAL_TREATMENT.md` UNRESOLVED
  - **Engineering posture:** Items remain open. Engineering cannot resolve them without creative approval.

- **White screen destination**
  - **Authority:** `PORTAL_TREATMENT.md` Emergence boundary
  - **Engineering posture:** The storyboard ends the Portal at Threshold. The illuminated white field belongs to Emergence and should be finalized in the Emergence engineering plan.

- **Depth mechanism within the approved monogram**
  - **Authority:** `PORTAL_TREATMENT.md` Part Three (UNRESOLVED)
  - **Engineering posture:** Engineering must implement depth **through** the approved PNG without redrawing or substituting the asset. The exact mechanism remains a creative decision.

---

## 4. Navigation During Portal Shots

**Authority:** `PORTAL_TREATMENT.md` Part Two (permanent, Version 1.0 revision).

Navigation is **not** hidden during Portal shots. It is **peripherally present** — quiet architecture, upper-right, reassurance not wayfinding.

| Portal phase | Nav state per Part Two |
|---|---|
| Shots 001–006 (Manifesto / Threshold / Portal passage) | **Available** — must recede below attention threshold; never animate for attention |
| Hero | N/A — Portal shots occur after Hero |

### Desktop nav specification (permanent visual decision)

- **Items:** Journal, Art, About, John Clark, Contact
- **Position:** upper-right corner
- **Style:** small, elegant, restrained; no background, glass, borders, or attention-seeking animation
- **Interaction:** subtle hover only

### Engineering responsibilities (navigation)

| Layer | Responsibility |
|---|---|
| HTML | Semantic `<nav>` with links; positioned outside cinematic focal layer; present in DOM from initial arrival onward |
| CSS | Fixed upper-right; low visual weight; `opacity`/`color` recede during peak cinematic beats; hover transition only |
| GSAP | Single quiet fade-in at initial arrival — **no** re-animation during Portal shots |
| Accessibility | Real links; keyboard reachable; visible focus indicator that remains subtle |

**Note:** Production `index.html` includes navigation markup (`css/navigation.css`, `js/navigation.js`). Portal integration must preserve Part Two presence rules without re-gating visibility on Feature 01 state.

---

## 5. Shot-by-Shot Engineering Plan

---

### Shot 001 — Stillness

**Storyboard authority:** The visitor reaches *"I'm planting my flag."* The declaration settles. Attention eventually returns to the NC monogram. Nothing asks the visitor to interact with it. Purpose: conviction settles.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Manifesto conclusion visible — final declaration fully legible; NC monogram revealed beneath (`PORTAL_TREATMENT.md` Beginning State) |
| **Automatic** | Stillness begins when declaration and monogram have settled in view (manifesto scroll complete OR declaration + monogram in view without further manifesto motion) |
| **Duration** | **Not specified in storyboard** — Treatment requires monogram register as signature with enough time and visual independence; exact duration is UNRESOLVED (`PORTAL_TREATMENT.md` UNRESOLVED) |

#### Camera behavior

| Behavior | Engineering interpretation |
|---|---|
| Frame | Static — no pan, zoom, or scroll during stillness |
| Focus | Declaration and monogram readable; monogram has visual independence beneath final line |
| Depth | No stage drift, breathe, or scroll-driven tension updates during this shot |

**Camera = viewport + manifesto stage transform.** No literal camera asset.

#### Scene state

| Layer | State |
|---|---|
| Background | Solid matte black `#080808` |
| Manifesto text | Final line visible; all prior lines may remain in scroll context above |
| Period | Ordinary punctuation on final line — no Portal behavior |
| NC monogram | Visible as author's signature — `.portal-signature` with approved PNG |
| Environmental tension | **Off or frozen** at settle value |
| Story Ring / social | Not visible — Crossing complete |
| Navigation | Present per Part Two — visually receded |
| Portal layer | Not active — monogram reads as signature only |
| Geography | Not present |

#### Animation timeline

| Time | Event |
|---|---|
| `T+0` | Declaration + monogram settle — all motion stops |
| `T+0 → T+hold` | Hold — zero intentional animation on declaration, monogram, or environment |
| `T+hold end` | Release to Shot 002 readiness (no visible change — Shot 002 is attention-driven) |

#### GSAP responsibilities

- Kill or pause `manifesto-tension.js` ScrollTrigger scrub and line clarity tweens on settle
- No new tweens during hold
- Optional: single master Portal timeline with `addPause()` for approved hold duration — **no** easing curves on content

#### CSS responsibilities

- `.cinema-manifesto__stage` — transform static
- `.portal-signature` — static; monogram at approved size (`min(16.5rem, 54vw)` per production baseline)
- `--env-drift-x`, `--env-drift-y`, `--env-breathe`, `--env-tension` — frozen if present
- Nav — existing presence complete; no pulse

#### HTML responsibilities

- Final manifesto line in DOM: *"I'm planting my flag."* — period inline as ordinary punctuation
- Monogram in DOM:

```html
<div class="portal-signature">
  <img
    src="assets/images/nc-monogram-watercolor.png"
    alt="Noèl Clark signature"
  />
</div>
```

- Portal state attribute on scene container, e.g. `data-portal-shot="001"` for debugging only — not visitor-visible

#### Asset requirements

- **Required:** `assets/images/nc-monogram-watercolor.png` — exact file only
- Approved manifesto typography (Inter / project body font)
- No substitute monogram assets, video, particles, or audio

#### Audio requirements

- **Silence** — no instructive chime, no ambient loop start (`FEATURE_01_TREATMENT.md` / Treatment Attention intent)
- If site-wide ambient exists elsewhere, it must not begin here without creative authority

#### Accessibility considerations

- Final declaration readable by screen readers as completed sentence
- Monogram `alt` describes signature — not "portal" or "click to enter"
- No `aria-live` announcements during stillness — silence is intentional
- `prefers-reduced-motion`: same hold; no substitute motion
- Focus must not trap; nav links remain reachable without breaking stillness intent

#### Responsive considerations

- Hold is wall-clock once triggered, not scroll-dependent
- Declaration + monogram remain visible without horizontal scroll at all breakpoints
- Monogram scales per production CSS — must retain signature legibility on mobile

#### Open questions

1. Exact trigger for "declaration settle" when visitor has scrolled manifesto — scroll position threshold not defined in creative authority.
2. Exact stillness hold duration — Treatment requires sufficient register time; storyboard does not specify seconds. **Blocked until approved.**

---

### Shot 002 — Recognition

**Storyboard authority:** The NC monogram remains familiar. At first, it is simply the mark already present within the remembered world. Then something about it becomes difficult to read as merely flat. Nothing announces this change. Purpose: doubt.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Shot 001 stillness complete (hold duration approved) |
| **Progression** | **Visitor-attention-driven** — no automatic visual state change marks Shot 002 entry |
| **Exit to Shot 003** | Creative authority does not specify a timer — see Open Questions |

#### Camera behavior

- Static frame — identical to Shot 001
- No zoom toward monogram
- No highlight, glow, pulse, or cursor affordance on monogram

#### Scene state

| Layer | State |
|---|---|
| Visual DOM | **Identical to Shot 001** at entry — deliberately no visible delta |
| NC monogram | Familiar watercolor signature appearance |
| Period | Ordinary punctuation — unchanged |
| Portal internal state | `recognition` — enables Shot 003 precondition only |
| Navigation | Available, receded |

#### Animation timeline

| Time | Event |
|---|---|
| Continuous | **No authorized animations** at Shot 002 entry |
| Internal | Optional invisible timer or interaction-idle detector to gate Shot 003 — **not defined in storyboard** |

#### GSAP responsibilities

- None visible
- Do not animate monogram, text, or environment

#### CSS responsibilities

- Maintain Shot 001 frozen state
- Monogram: no `:hover` scale, glow, `cursor:pointer`, pulse, or ring that "screams interactivity" (Treatment Attention intent)
- If cursor change permitted: must feel "near something real" — extremely subtle; **not approved explicitly in storyboard**

#### HTML responsibilities

- Unchanged from Shot 001
- Monogram remains `<img>` with signature alt text — not relabeled as interactive

#### Asset requirements

- Same approved PNG — no asset swap

#### Audio requirements

- Continued silence

#### Accessibility considerations

- **Engineering cannot implement "feels wrong"** — that is visitor perception
- No deceptive `aria-hidden` on manifesto content
- Screen reader experience: unchanged text and signature description — acceptable; doubt is visual/perceptual
- Document in QA: Shot 002 success criteria are experiential, not DOM-diff testable

#### Responsive considerations

- Same as Shot 001 — visual parity across breakpoints

#### Open questions — engineering stop

1. **No creative authority defines Shot 002 duration or exit condition.** Engineering cannot auto-advance to Shot 003 without either (a) a defined minimum dwell time, or (b) visitor interaction. **Blocked until approved.**
2. **"Attention drifts" is not machine-detectable** without inventing scroll/focus heuristics. Any idle-detection threshold would be an engineering invention.

**Recommended authority resolution (not implementation):** Define whether Shot 002 merges with 001+003 timing (stillness then depth) or requires explicit creative sign-off on idle duration.

---

### Shot 003 — Impossible Depth

**Storyboard authority:** The NC monogram has an inside. Its familiar form remains recognizable, but depth now exists where depth should not exist. What lies beyond remains unknown to the visitor. The monogram has become a threshold. Purpose: curiosity.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | **Blocked** until Shot 002 exit is defined — see above |
| **Working assumption (requires approval)** | Minimum dwell after Shot 001 + additional stillness matching Treatment "until attention arrives at monogram" |
| **Authority constraint** | Depth must emerge **inside** the approved monogram without recreating, redrawing, or substituting the asset |

#### Camera behavior

- Static frame
- **No zoom** into monogram
- **No scale transform** on monogram exterior — footprint must remain production baseline size
- Optional imperceptible vignette shift on environment — only if it does not read as "spotlight on monogram"

#### Scene state

| Layer | State |
|---|---|
| Monogram exterior | Same size, same watercolor identity as Shot 001 — reads as ordinary signature |
| Monogram interior | Depth emerges **inside** existing mark boundary — pigment/watercolor possesses an inside |
| Manifesto text | Unchanged — no blur, dissolve, or movement on typography |
| Period | Unchanged — ordinary punctuation |
| Story Ring | Not visible unless separately authorized for this shot |
| Geography | Not visible |

#### Animation timeline

| Phase | Behavior | Authority |
|---|---|---|
| Rest | Flat signature | Shot 001–002 |
| Deepening | Interior depth animates inward — exact duration **UNRESOLVED** | `PORTAL_TREATMENT.md` UNRESOLVED |
| End state | Monogram reads as having depth; still recognizably itself — not larger, glowing, or button-like | Storyboard, Treatment Impossible Depth |

#### GSAP responsibilities

- Depth animation mechanism **pending creative lock** — may use GSAP on overlay/mask layers, CSS custom properties, or canvas compositing **over** the approved PNG
- **Never** `scale`, `width`, `height`, `filter: brightness`, outer glow, or generic portal effects on monogram exterior
- Timeline starts on approved Shot 003 entry trigger only
- Must preserve exact asset as visible source — no vector redraw of monogram

#### CSS responsibilities

- Monogram container (production baseline):

```html
<div class="portal-signature">
  <img
    src="assets/images/nc-monogram-watercolor.png"
    alt="Noèl Clark signature"
  />
  <!-- Depth layers TBD — must not replace img src -->
</div>
```

- Depth layers — if used — constrained within monogram bounding box
- Interior depth: pigment/void/watercolor texture animates from flat to deep — **exact visual mechanism UNRESOLVED**
- No outer glow, ring, doorway, or button styling
- `mix-blend-mode` — test against `#080808` black field
- `@media (prefers-reduced-motion: reduce)` — jump to end depth state or static threshold per approved reduced-motion treatment (**UNRESOLVED**)

#### HTML responsibilities

- Monogram structure must support interior depth without removing approved `<img>` asset
- Final line + monogram remain semantically separate — period stays on manifesto line
- Interior depth layers `aria-hidden="true"` if decorative
- Do not change `alt` to imply interactivity

#### Asset requirements

- **Required:** `assets/images/nc-monogram-watercolor.png` — exact file only
- Depth may use CSS gradients, masks, or compositing layers — **not** a substitute monogram image
- No video loop inside mark unless separately approved

#### Audio requirements

- Silence continues
- Treatment mentions water/moon at passage completion — **not authorized in Shot 003** (nothing identifiable)

#### Accessibility considerations

- Sentence remains grammatically complete for screen readers
- Depth effect is decorative — `prefers-reduced-motion` behavior **UNRESOLVED**
- Do not rely on color alone for "depth" — shape/interior contrast must work in high contrast mode
- Keyboard: monogram must not become focus trap unless Crossing (004) assigns intentional focusable target — **unresolved**

#### Responsive considerations

- Monogram size per production CSS — scales with viewport
- Interior depth must remain contained within mark bounds at all DPIs
- Monogram touch target at production size (`min(16.5rem, 54vw)`) exceeds minimum touch guidelines — favorable vs. punctuation-only threshold

#### Open questions — engineering stop

1. Shot 003 entry trigger undefined (depends on Shot 002).
2. **Exact visual mechanism** for watercolor impossible depth — `PORTAL_TREATMENT.md` UNRESOLVED.
3. Production animation duration — not specified in storyboard or Treatment.
4. **Asset integrity:** how to achieve interior depth while using exact PNG without recreation — requires creative + engineering joint approval.

---

### Shot 004 — Crossing

**Storyboard authority:** The visitor chooses to enter through the NC monogram. Nothing pulls them. Nothing forces them. The remembered world gives way as the visitor crosses the threshold. Crossing is consent. Purpose: commitment.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Shot 003 depth state reached (or visitor perceives depth — timing TBD) |
| **Crossing initiation** | **Explicit visitor action only** — click, tap, or keyboard activation on monogram |
| **Forbidden** | Auto-advance, timeout pull, scroll hijack, parallax suction |

#### Camera behavior

- On crossing initiation: transition begins — **exact motion not specified in storyboard**
- Treatment narrative: attention moves **into** the watercolor monogram; scale and spatial understanding change
- Visitor crosses **through the signature** — not a scene load (`PORTAL_TREATMENT.md` Crossing)
- Remembered world gives way — manifesto field recedes

#### Scene state

| Phase | State |
|---|---|
| Pre-crossing | Manifesto + deep monogram visible; nav available |
| Crossing active | Passage begins — handoff to Shot 005 |
| Forbidden | Geography visible; present-day home; Emergence white field |

#### Animation timeline

| Event | Authority |
|---|---|
| Visitor action | Consent signal on monogram |
| Response | Begin passage — **duration not in storyboard** |
| Manifesto | Recedes or yields attention — Treatment: familiar page gives way — **no specified DOM behavior** |

**Engineering stop:** Storyboard defines consent requirement and monogram target. Crossing animation, duration, and exact spatial mechanics remain **UNRESOLVED** (`PORTAL_TREATMENT.md` UNRESOLVED).

#### GSAP responsibilities (conditional — pending creative lock)

- Own crossing timeline once trigger fires
- Coordinate: manifesto fade/recede, monogram depth expansion **without** exterior scale increase (if approved), passage continuity with watercolor material identity
- Must not begin Emergence
- Single timeline owner — prevent duplicate triggers

#### CSS responsibilities

- Interactive target styling: **no button appearance**
- If hover state exists: "cursor near something real" — extremely subtle
- `pointer-events` scoped to monogram crossing target only — not period, not manifesto body
- During crossing: lock scroll if passage requires full-frame control — **requires approval** (Arrival precedent: scroll lock during transition)

#### HTML responsibilities

- Crossing target: monogram wrapper or approved focusable child — must be keyboard activatable (`<button>` visually unstyled or `role="button"` on wrapper) — **must not look like button**
- `aria-label` must not instruct ("Click to enter") — violates curiosity-not-instruction
- Prefer no label; treat as signature if possible — **accessibility conflict unresolved**

#### Asset requirements

- Approved monogram PNG throughout crossing — no generic portal imagery
- Passage texture/void — **not specified**; watercolor/pigment traces may appear per Treatment Passage — **UNRESOLVED**

#### Audio requirements

- No instructive chime
- Optional ambient begin at crossing — **not authorized in storyboard** — blocked

#### Accessibility considerations

- **Critical conflict:** consent crossing requires discoverable interaction; Treatment forbids obvious UI and instruction
- Keyboard path mandatory — but labeling cannot say "Enter"
- `prefers-reduced-motion`: crossing must complete without disorientation — provide reduced passage variant (**UNRESOLVED**)
- Focus management during crossing — TBD

#### Responsive considerations

- Monogram production size provides adequate touch target on mobile
- Crossing must work on touch devices without hover

#### Open questions — engineering stop

1. **Crossing animation specification:** absent — cannot implement passage visual.
2. **Exact camera/spatial behavior during crossing** — UNRESOLVED.
3. **Accessibility vs. curiosity:** unresolved without creative decision on focusable monogram and labeling strategy.

---

### Shot 005 — Between

**Storyboard authority:** Darkness. No horizon. No orientation. No destination. No up. No down. The remembered world has ended. The next world has not yet begun. Purpose: passage.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Shot 004 crossing initiated |
| **Exit** | Transition to Shot 006 — **duration not specified** |

#### Camera behavior

- No orienting horizon line
- No ground plane
- No directional light source implying "up"
- Uniform or near-uniform darkness — **not** Geography night scene

**Engineering interpretation:** `#000000` or `#080808` field with **no identifiable features** — distinct from Geography moonlight (`WORLD_OF_CURIOSITY.md`), which is Emergence.

#### Scene state

| Layer | State |
|---|---|
| Manifesto | Not visible — remembered world ended |
| Monogram | Not identifiable as signature — passage underway or complete |
| Navigation | Part Two: remains available — must be nearly invisible against dark field or ethically receded; **visual spec conflict: nav text on pure black** |
| Geography | **Absent** |
| Horizon / stars / river | **Absent** |

Treatment Passage: traces of watercolor, pigment, depth, movement, sound, texture, or light **may** exist — must not establish recognizable Geography.

#### Animation timeline

| Phase | Behavior |
|---|---|
| Entry | Fade or cut to featureless dark — **method not specified** |
| Hold | Passage duration undefined |
| Exit | Faintest suggestion begins (Shot 006) |

#### GSAP responsibilities

- Fade out manifesto + monogram layer
- Hold dark state
- Prepare Shot 006 subtle luminance — **must not reveal landscape**
- Optional: imperceptible pigment/watercolor traces per Treatment — **UNRESOLVED**

#### CSS responsibilities

- Full-viewport dark layer `.portal-between`
- `visibility` / `opacity` on scene-02 content
- No gradients implying horizon (no vertical ground/sky split)
- Nav: maintain presence per Part Two — opacity may drop further; links remain in DOM

#### HTML responsibilities

- Portal passage container — empty or near-empty
- No `<img>` of landscape or Geography
- No text

#### Asset requirements

- None authorized for identifiable Geography
- Passage fragments — if any — must not resolve into Emergence content (**UNRESOLVED**)

#### Audio requirements

- Silence — Treatment's distant water **not authorized here** unless approved as non-identifying trace (**UNRESOLVED**)

#### Accessibility considerations

- Page title / document context must not disappear from assistive tech without landmark
- Consider visually-hidden status region — **copy not authorized** — Open Question
- Reduced motion: shorter hold acceptable if passage duration approved

#### Responsive considerations

- Full viewport coverage all breakpoints
- No orientation-dependent layout

#### Open questions — engineering stop

1. **Passage duration** — undefined.
2. **Nav on pure black** — Part Two requires availability; storyboard requires darkness with no competitors. Engineering needs rule for nav visibility floor during Between.
3. **Which passage fragments are perceptible** — Treatment UNRESOLVED.

---

### Shot 006 — Threshold

**Storyboard authority:** The faintest suggestion that another world exists. Nothing is yet identifiable. No landscape is revealed. No Geography is shown. The visitor only senses that something lies beyond the darkness. **Portal ends here.** Purpose: anticipation.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Shot 005 hold complete — **duration undefined** |
| **Exit** | Portal complete → handoff to **Emergence** |

#### Camera behavior

- Still no horizon, no landscape framing
- **Faintest suggestion only** — sub-perceptual luminance shift, grain, or breath in darkness
- **Cannot** show: moon, river, magnolia, deer, stars, paths (`WORLD_OF_CURIOSITY.md` content belongs to Emergence)

#### Scene state

| Layer | State |
|---|---|
| Visual | Near-black with hint of "beyond" — not identifiable |
| Geography | **Not shown** |
| Portal | Terminal state |
| Emergence | Prepared but not active |
| Navigation | Available per Part Two |

#### Animation timeline

| Phase | Behavior |
|---|---|
| Suggestion emerges | Extremely subtle — slower than attention |
| Hold | Anticipation — duration undefined |
| Complete | Emergence assumes arrival |

#### GSAP responsibilities

- Micro-luminance or noise animation — **amplitude must stay below identification threshold**
- Explicit `onComplete` → Portal exit condition → Emergence entry signal
- Do not render Geography assets

#### CSS responsibilities

- Optional: CSS noise overlay at `<1%` opacity
- Optional: radial gradient so faint it reads as "not quite black" — **must not resolve into moon or river**
- No Geography images

#### HTML responsibilities

- Empty passage container or canvas — no Geography markup
- `data-portal-shot="006"` for state debugging

#### Asset requirements

- None — if suggestion requires texture, CSS-only unless creative supplies asset

#### Audio requirements

- Unauthorized: river, music, bowl vibration (`WORLD_OF_CURIOSITY.md`)
- Silence or near-silence unless Emergence authority adds audio

#### Accessibility considerations

- Portal ending must hand off to Emergence with document structure intact
- Avoid infinite dark trap — Emergence must follow with defined trigger
- `prefers-reduced-motion`: static faint suggestion or skip to Emergence entry

#### Responsive considerations

- Suggestion must remain sub-identifiable on OLED black vs. LCD black
- Test mobile brightness settings — faint glow may vanish or become obvious

#### Open questions — engineering stop

1. **Suggestion mechanism** — creative authority says "faintest" but does not specify visual channel. Any identifiable shape fails storyboard.
2. **Threshold hold duration** before Emergence — undefined.
3. **Exact Portal → Emergence spatial handoff point** — UNRESOLVED (`PORTAL_TREATMENT.md`).

---

## 6. Portal Exit and Emergence Handoff

### Portal exit condition (engineering)

Portal completes when:

1. Shot 006 threshold state reached
2. Anticipation hold satisfied (duration TBD)
3. Emergence module receives handoff signal
4. Remembered environment is no longer progression authority
5. **Geography still not revealed by Portal**

### Emergence entry (out of scope — boundary only)

Emergence owns:

- First reveal of Geography of Curiosity
- Arrival into present-day NoelClark.com
- Night → day → full illumination progression
- **PLANT YOUR FLAG** on illuminated white screen — closing invitation; **distinct** from opening PLANT YOUR FLAG
- Navigation continues per Part Two

Portal must **not**:

- Load Geography assets
- Play Geography audio
- Establish present-day home as authoritative
- Reveal identifiable landscape, moon, river, deer, or magnolia
- Animate flag into Portal passage (flag decisions superseded for Portal entry)

---

## 7. Production Dependencies

### Existing production files (inspected)

| File | Current Portal relevance |
|---|---|
| `index.html` | Manifesto markup; `.portal-signature` with approved monogram PNG; navigation present |
| `css/cinema.css` | Manifesto typography |
| `css/arrival.css` | Scene 02 shell; `.portal-signature` reveal styling |
| `css/navigation.css` | Part Two desktop nav — upper-right, restrained |
| `js/arrival.js` | Crossing into manifesto — stops at manifesto clarity |
| `js/navigation.js` | Nav presence from initial arrival |
| `js/manifesto-tension.js` | Scroll-driven line clarity — **must stop before Shot 001** |
| `js/feature-01-state.js` | Feature 01 state tracking — Portal states to integrate |

### New modules required (names TBD from repo conventions)

| Module | Responsibility |
|---|---|
| `js/portal.js` (or equivalent) | Shot state machine, timers, crossing consent, Emergence handoff |
| `css/portal.css` | Between, Threshold, monogram depth layers (black field) |

### GSAP usage summary

| Shot | GSAP |
|---|---|
| 001 | Pause/kill tension tweens |
| 002 | None |
| 003 | Conditional — depth mechanism TBD; may prefer CSS/compositing |
| 004 | Crossing timeline (spec TBD) |
| 005 | Fade to dark |
| 006 | Micro suggestion tween (spec TBD) |

### External libraries

- GSAP 3.13 + ScrollTrigger — already loaded in `index.html`
- No new libraries without justification

---

## 8. QA Acceptance Criteria (Per Shot)

| Shot | Pass condition |
|---|---|
| 001 | Zero visible motion during hold; tension off; monogram visible as signature; period ordinary |
| 002 | No visible DOM change from 001; monogram unchanged |
| 003 | Depth inside monogram; watercolor identity preserved; size/glow/button styling unchanged |
| 004 | Crossing only on visitor action through monogram |
| 005 | No horizon, no Geography, no orientation |
| 006 | Suggestion only; nothing identifiable; Portal ends |

---

## 9. Consolidated Open Questions (Implementation Blockers)

Questions must be answered in creative authority — not inferred by engineering.

### Resolved (approved architecture)

- NC watercolor monogram is the Portal symbol
- Period is punctuation only — not Portal
- Story Ring is continuous social world — not Portal symbol or crossing target
- Crossing interaction target is the NC monogram
- Navigation timing — persistent from initial arrival per Part Two + DECISIONS
- Flag beat placement — superseded for Portal entry; monogram is threshold
- Portal ends at Threshold; Geography revealed by Emergence only

### Open — requires creative authority

1. Shot 001 stillness hold duration — sufficient monogram register time not quantified  
2. Shot 002 duration and exit condition  
3. Shot 003 entry trigger (depends on Shot 002)  
4. **Exact visual mechanism** for watercolor impossible depth inside approved asset  
5. Depth animation duration  
6. Reduced-motion treatment for depth and crossing  
7. Accessibility strategy for wordless consent threshold on monogram  
8. Crossing animation specification, spatial behavior, and duration  
9. Which visual/sound fragments — if any — are perceptible during Passage  
10. Shot 005 passage duration  
11. Nav visibility floor during Between/Threshold darkness  
12. Shot 006 suggestion mechanism and hold duration  
13. Exact Portal → Emergence spatial handoff point  
14. Portal sound design / music — if any  

---

## 10. Engineering Principles for This Feature

1. **Implement only what is approved. When authority is silent, engineering must preserve discovery rather than invent it. Stop and ask.**
2. **Do not redesign shots** for convenience.
3. **Portal never reveals Geography.**
4. **Crossing is consent.**
5. **The NC monogram is the Portal** — use exact asset; preserve watercolor signature identity throughout Recognition and depth.
6. **The period is not the Portal** — manifesto punctuation remains untouched by Portal engineering.
7. **Navigation serves experience; experience never serves navigation** (`PORTAL_TREATMENT.md` Permanent Navigation Rule).
8. **Do not recreate the monogram** — depth and crossing must work through the approved PNG, not a substitute graphic.

---

*End of Portal Engineering Plan — Version 1.2.*
