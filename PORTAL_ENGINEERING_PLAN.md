# PORTAL_ENGINEERING_PLAN.md

**Version:** 1.1  
**Status:** Engineering translation — not implementation authority  
**Scope:** Portal storyboard shots 001–006, revised per Continuous Experience Architecture  

---

## Architectural Revision (v1.1)

**Authority:** `docs/DECISIONS.md` — Continuous Experience Architecture (Version 1.0); `PORTAL_TREATMENT.md` Part Three.

This revision changes **flow only**. Approved visuals remain intact.

### Continuous scroll model

```
Hero
  ↓  scroll
Remembered social world (Story Ring — not a separate destination)
  ↓  scroll (after social dissolve trigger)
Approved Manifesto (production baseline — appearance unchanged)
  ↓  scroll
NC monogram → Portal
```

- **No page transitions** anywhere in this sequence.
- Manifesto is **not rebuilt** — same typography, copy, black field, hierarchy, composition.
- Social dissolve removes **interface chrome only** — words remain.
- **Portal symbol (permanent):** `assets/images/nc-monogram-watercolor.png` — exact asset; no recreation.

### Blocker resolution status (v1.1)

| # | Blocker | Status |
|---|---|---|
| 11 | Flag beat placement vs. storyboard | **Resolved** — monogram is Portal symbol; flag decisions superseded for Portal entry |
| 5 | Crossing interaction target | **Resolved** — NC monogram |
| 12 | Navigation timing conflict | **Resolved** — persistent global navigation from initial arrival (`PORTAL_TREATMENT.md` Part Two + DECISIONS) |
| 14 | Story Ring reappearance during Portal | **Resolved** — Story Ring is continuous social world; not Portal symbol |
| 4 | Period touch target vs. not larger | **N/A** — period is not Portal interaction target |
| 1–3 | Shot 002/003 period mechanics | **Requires storyboard reconciliation** — shots authored for period threshold |
| 6–10, 13 | Crossing animation, durations, Emergence boundary | **Open** — unchanged by flow decision |

**Engineering note:** `PORTAL_STORYBOARD.md` shots 001–006 describe period-centric threshold beats. The monogram Portal symbol requires creative reconciliation of storyboard shots to monogram — **not an engineering inference task.**

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
- Engineering may not redesign shots, invent UI, or substitute creative intent with convenient implementation.

### Scope of this plan

| In scope | Out of scope |
|---|---|
| Shots 001–006 | Emergence shots |
| Engineering handoff Portal → Emergence | Geography implementation |
| Navigation presence rules from `PORTAL_TREATMENT.md` Part Two | Flag beat details not present in storyboard |
| Pre-condition entry from Planting My Flag | Return-visitor Feature 08 |

---

## 2. Entry Preconditions

Portal engineering begins only after **Planting My Flag** has satisfied its approved exit condition (`docs/ROADMAP.md`, `docs/DECISIONS.md`).

### Required beginning state (Shot 001 entry)

| Condition | Authority |
|---|---|
| Visitor has scrolled through Hero and remembered social world | Continuous Experience Architecture |
| Social interface dissolve complete — words remain, chrome gone | `docs/DECISIONS.md` |
| Approved manifesto visible — **production baseline appearance unchanged** | Continuous Experience Architecture |
| Manifesto copy verbatim — continuous bridge + declaration ending in final declaration | Approved copy — `MANIFESTO_TREATMENT.md` |
| NC monogram follows manifesto as final encountered object | `docs/DECISIONS.md` |
| Emergence / present-day home not yet authoritative | `docs/DECISIONS.md` |

### Symbolic objects in play

| Object | Approved role |
|---|---|
| NC monogram (`assets/images/nc-monogram-watercolor.png`) | Portal symbol — signature becomes Portal |
| Story Ring | Continuous social world — not Portal destination or symbol |
| Final period | Manifesto punctuation only — not Portal interaction target |
| Approved manifesto | Production baseline — scroll position changes only |

~~**Engineering stop — flag vs. storyboard:**~~ **Resolved v1.1** — monogram is Portal symbol; flag decisions superseded for Portal entry.

---

## 3. Global Architecture

### 3.1 Portal state model

```
Hero + social world (continuous scroll)
        ↓
Social dissolve → approved Manifesto revealed (appearance unchanged)
        ↓
Visitor reads manifesto → encounters NC monogram
        ↓
[Shot 001] Stillness          ← storyboard reconciliation required (period → monogram)
        ↓
[Shots 002–006]               ← pending storyboard reconciliation
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
| Period threshold behavior | **Superseded** — monogram is Portal symbol |
| Passage (Between, Threshold) | Portal module |
| Geography reveal | Emergence — **not Portal** |
| Navigation markup and presence | Navigation module — governed by `PORTAL_TREATMENT.md` Part Two |
| Story Ring identity continuity | Arrival / persistent circular element |

### 3.3 Global engineering rules

1. **No attention-seeking UI** at threshold — no buttons, arrows, chevrons, progress bars, instructional copy, pulsing period (`PORTAL_TREATMENT.md` stub sections: Attention, Things Portal Must Never Become; narrative in `FEATURE_01_TREATMENT.md`).
2. **Visitor moves toward Portal.** Portal does not move toward visitor (`docs/DECISIONS.md`).
3. **Crossing is consent.** No auto-pull, no forced progression (Shot 004).
4. **Silence is material** (`WORLD_OF_CURIOSITY.md`, Shot 001).
5. **Manifesto environmental tension** (`js/manifesto-tension.js`) must **cease or recede** before Shot 001 stillness — motion during "nothing moves" violates storyboard.
6. **Navigation** per revised Part Two: globally available from initial arrival; present during Manifesto, Threshold, Portal — must not compete with cinematic beats.

### 3.4 Document conflicts requiring resolution before implementation

| Conflict | Documents | Engineering posture |
|---|---|---|
| ~~Navigation timing~~ | ~~Part Two vs. DECISIONS vs. Master Brief~~ | **Resolved v1.1** — DECISIONS Continuous Experience + Part Two |
| ~~Flag sequence~~ | ~~DECISIONS flag vs. storyboard~~ | **Resolved v1.1** — monogram is Portal symbol |
| ~~Passage symbol~~ | ~~Period vs. Story Ring~~ | **Resolved v1.1** — monogram; Story Ring is social world only |
| Storyboard vs. monogram | `PORTAL_STORYBOARD.md` period-centric shots vs. monogram Portal symbol | **Open** — requires creative reconciliation of shots 001–006 |
| Portal Treatment Part One | `PORTAL_TREATMENT.md` lines 14–38 are section stubs only | Threshold behavior from storyboard + Part Three — not from empty stubs |
| White screen destination | `PORTAL_TREATMENT.md` purpose line references illuminated white screen | Storyboard ends Portal at Threshold. Confirm Emergence ownership |

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
| HTML | Semantic `<nav>` with links; positioned outside cinematic focal layer; present in DOM from Story Ring fade-in onward |
| CSS | Fixed upper-right; low visual weight; `opacity`/`color` recede during peak cinematic beats; hover transition only |
| GSAP | Single quiet fade-in at Story Ring entry — **no** re-animation during Portal shots |
| Accessibility | Real links; keyboard reachable; visible focus indicator that remains subtle |

**Note:** Production `index.html` currently has **no navigation markup**. Navigation engineering is a dependency for Portal integration but is not defined shot-by-shot below except where presence rules apply.

---

## 5. Shot-by-Shot Engineering Plan

---

### Shot 001 — Stillness

**Storyboard authority:** Visitor reaches *"I'm planting my flag."* Nothing moves. Nothing changes. Silence. ~3 seconds. Purpose: conviction settles.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Planting My Flag exit condition satisfied — final declaration fully visible and legible |
| **Automatic** | Stillness timer begins on declaration settle (manifesto scroll complete OR declaration in view without further manifesto motion) |
| **Duration** | Approximately **3 seconds** (`PORTAL_STORYBOARD.md`) |

#### Camera behavior

| Behavior | Engineering interpretation |
|---|---|
| Frame | Static — no pan, zoom, or scroll during stillness |
| Focus | Full frame on declaration; final line including period is readable |
| Depth | No stage drift, breathe, or scroll-driven tension updates during this shot |

**Camera = viewport + manifesto stage transform.** No literal camera asset.

#### Scene state

| Layer | State |
|---|---|
| Background | Solid matte black `#080808` |
| Manifesto text | Final line visible; all prior lines may remain in scroll context above |
| Period | Ordinary punctuation — no depth effect active |
| Environmental tension | **Off or frozen** at settle value |
| Story Ring / social | Not visible — Crossing complete |
| Navigation | Present per Part Two — visually receded |
| Portal layer | Not active |
| Geography | Not present |

#### Animation timeline

| Time | Event |
|---|---|
| `T+0` | Declaration settle — all motion stops |
| `T+0 → T+3000ms` | Hold — zero intentional animation |
| `T+3000ms` | Release to Shot 002 readiness (no visible change — Shot 002 is attention-driven) |

#### GSAP responsibilities

- Kill or pause `manifesto-tension.js` ambient drift, breathe, and ScrollTrigger scrub on settle
- No new tweens during hold
- Optional: single master Portal timeline with `addPause()` for 3000ms — **no** easing curves on content

#### CSS responsibilities

- `.cinema-manifesto__stage` — transform static
- `--env-drift-x`, `--env-drift-y`, `--env-breathe`, `--env-tension` — frozen
- Period — default typographic rendering (inline text or inline period element at rest)
- Nav — existing fade-in complete; no pulse

#### HTML responsibilities

- Final manifesto line in DOM: *"I'm planting my flag."* with period as text or dedicated `.period` / `.period-dot` element (structure TBD — must exist before Shot 003)
- Portal state attribute on scene container, e.g. `data-portal-shot="001"` for debugging only — not visitor-visible

#### Asset requirements

- None beyond approved manifesto typography (Inter / project body font)
- No images, video, particles, or audio

#### Audio requirements

- **Silence** — no instructive chime, no ambient loop start (`FEATURE_01_TREATMENT.md` / Treatment Attention stub intent)
- If site-wide ambient exists elsewhere, it must not begin here without creative authority

#### Accessibility considerations

- Final declaration readable by screen readers as completed sentence
- No `aria-live` announcements during stillness — silence is intentional
- `prefers-reduced-motion`: same 3s hold; no substitute motion
- Focus must not trap; nav links remain reachable without breaking stillness intent

#### Responsive considerations

- 3s hold is wall-clock, not scroll-dependent
- Declaration + period remain visible without horizontal scroll at all breakpoints
- Period must not wrap to orphan line at narrow widths — typography CSS must keep period attached to final word

#### Open questions

1. Exact trigger for "declaration settle" when visitor has scrolled manifesto — scroll position threshold not defined in creative authority.
2. Whether stillness duration is strictly 3000ms or "until body readiness" (Treatment narrative) — storyboard specifies ~3 seconds; Treatment describes attention-based silence end. **Storyboard duration governs engineering timer; Shot 002–003 bridge attention.**

---

### Shot 002 — Recognition

**Storyboard authority:** Period remains ordinary punctuation. Visitor's attention drifts. When it returns, something feels wrong, although nothing has visibly changed. Purpose: doubt.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Shot 001 stillness complete (~3s elapsed) |
| **Progression** | **Visitor-attention-driven** — no automatic visual state change marks Shot 002 entry |
| **Exit to Shot 003** | Creative authority does not specify a timer — see Open Questions |

#### Camera behavior

- Static frame — identical to Shot 001
- No zoom toward period
- No highlight, glow, or cursor affordance on period

#### Scene state

| Layer | State |
|---|---|
| Visual DOM | **Identical to Shot 001** — deliberately no visible delta |
| Period | Ordinary punctuation appearance |
| Portal internal state | `recognition` — enables Shot 003 precondition only |
| Navigation | Available, receded |

#### Animation timeline

| Time | Event |
|---|---|
| Continuous | **No authorized animations** |
| Internal | Optional invisible timer or interaction-idle detector to gate Shot 003 — **not defined in storyboard** |

#### GSAP responsibilities

- None visible
- Do not animate period, text, or environment

#### CSS responsibilities

- Maintain Shot 001 frozen state
- Period: no `:hover` scale, glow, or cursor:pointer that "screams interactivity" (Treatment Attention intent)
- If cursor change permitted: must feel "near something real" — extremely subtle; **not approved explicitly in storyboard**

#### HTML responsibilities

- Unchanged from Shot 001
- Period in DOM as normal inline content

#### Asset requirements

- None

#### Audio requirements

- Continued silence

#### Accessibility considerations

- **Engineering cannot implement "feels wrong"** — that is visitor perception
- No deceptive `aria-hidden` on content
- Screen reader experience: unchanged text — acceptable; doubt is visual/perceptual
- Document in QA: Shot 002 success criteria are experiential, not DOM-diff testable

#### Responsive considerations

- Same as Shot 001 — visual parity across breakpoints

#### Open questions — engineering stop

1. **No creative authority defines Shot 002 duration or exit condition.** Engineering cannot auto-advance to Shot 003 without either (a) a defined minimum dwell time, or (b) visitor interaction. **Blocked until approved.**
2. **"Attention drifts" is not machine-detectable** without inventing scroll/focus heuristics. Any idle-detection threshold would be an engineering invention.

**Recommended authority resolution (not implementation):** Define whether Shot 002 merges with 001+003 timing (stillness then depth) or requires explicit creative sign-off on idle duration.

---

### Shot 003 — Impossible Depth

**Storyboard authority:** Period is not larger, not brighter, not glowing. It simply possesses an inside. Purpose: curiosity.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | **Blocked** until Shot 002 exit is defined — see above |
| **Working assumption (requires approval)** | Minimum dwell after Shot 001 (~3s) + additional stillness matching Treatment "until attention arrives at period" |
| **Prototype reference** | `PERIOD-PROTOTYPE/` — demonstrates depth without scale change; **not production visual** (prototype uses paper/journal background; production uses black manifesto) |

#### Camera behavior

- Static frame
- **No zoom** into period
- **No scale transform** on period — footprint must remain `~0.31em` equivalent (prototype convention)
- Optional imperceptible vignette shift on environment — only if it does not read as "spotlight on period"

#### Scene state

| Layer | State |
|---|---|
| Period exterior | Same size, same brightness as Shot 001 — reads as ordinary dot |
| Period interior | Void depth emerges **inside** existing dot boundary |
| Manifesto text | Unchanged — no blur, dissolve, or movement on typography |
| Story Ring | Not visible unless separately authorized for this shot |
| Geography | Not visible |

#### Animation timeline

| Phase | Behavior | Reference |
|---|---|---|
| Rest | Flat period | Shot 001–002 |
| Deepening | Interior void gradient animates inward — **18s** easing in prototype | `PERIOD-PROTOTYPE/style.css` — **timing requires creative approval for production** |
| End state | Period reads as having depth; still not larger/brighter/glowing | Storyboard |

#### GSAP responsibilities

- **Preferred:** CSS `@keyframes` on interior layers (prototype pattern) — GSAP not required if CSS handles depth
- If GSAP used: animate custom properties for gradient stops only — **never** `scale`, `width`, `height`, `filter: brightness`, or glow box-shadow on period exterior
- Timeline starts on approved Shot 003 entry trigger only

#### CSS responsibilities

- Period structure (from prototype pattern, adapted to black field):

```html
<!-- Structural reference only — not implementation -->
<span class="period">
  <span class="period-dot">
    <span class="period-dot__void"></span>
    <span class="period-dot__rim"></span>
  </span>
</span>
```

- `.period-dot` — fixed em dimensions; `overflow: hidden`; `border-radius: 50%`
- `.period-dot__void` — radial gradient animates from flat to deep interior black
- `.period-dot__rim` — subtle interior rim light; **not** outer glow
- `mix-blend-mode` — test against `#080808` — prototype used paper; **black-field result requires visual approval**
- `@media (prefers-reduced-motion: reduce)` — jump to end depth state (prototype pattern)

#### HTML responsibilities

- Period must be markup, not bare `.` character, to support interior layers
- Final line: *I'm planting my flag* + period structure
- `aria` — period remains part of readable sentence; interior layers `aria-hidden="true"`

#### Asset requirements

- None — CSS gradients only
- **No** image textures inside period
- **No** video loop

#### Audio requirements

- Silence continues
- Treatment mentions water/moon at passage completion — **not authorized in Shot 003** (nothing identifiable)

#### Accessibility considerations

- Sentence remains grammatically complete for screen readers
- Depth effect is decorative — `prefers-reduced-motion` shows static deep state
- Do not rely on color alone for "depth" — shape/interior contrast must work in high contrast mode
- Keyboard: period must not be focus trap unless Crossing (004) assigns intentional focusable target — **unresolved**

#### Responsive considerations

- Period size in `em` — scales with declaration font size
- Interior depth must remain contained at all DPIs — test `0.31em` minimum legibility on mobile
- No tap target enlargement — **conflicts with mobile touch accuracy** — see Open Questions

#### Open questions — engineering stop

1. Shot 003 entry trigger undefined (depends on Shot 002).
2. Production animation duration — prototype uses 18s; storyboard does not specify.
3. **Mobile touch target:** period at ~0.31em may be below accessible touch minimum without violating "not larger." **Cannot implement Crossing on period at mobile sizes without creative exception or invisible hit area — invisible hit area may violate "nothing visibly changed" in Shot 002.**

---

### Shot 004 — Crossing

**Storyboard authority:** Visitor chooses to enter. Nothing pulls. Nothing forces. Crossing is consent. Purpose: commitment.

#### Trigger

| Trigger | Detail |
|---|---|
| **Entry** | Shot 003 depth state reached (or visitor perceives depth — timing TBD) |
| **Crossing initiation** | **Explicit visitor action only** — click, tap, or keyboard activation |
| **Forbidden** | Auto-advance, timeout pull, scroll hijack, parallax suction |

#### Camera behavior

- On crossing initiation: transition begins — **exact motion not specified in storyboard**
- Treatment narrative: "visitor moves through" — implies forward passage, not cut
- Flag remains planted behind — does not follow (`docs/DECISIONS.md`)

#### Scene state

| Phase | State |
|---|---|
| Pre-crossing | Manifesto + deep period visible; nav available |
| Crossing active | Passage begins — handoff to Shot 005 |
| Forbidden | Geography visible; present-day home; white screen title |

#### Animation timeline

| Event | Authority |
|---|---|
| Visitor action | Consent signal |
| Response | Begin passage — **duration not in storyboard** |
| Manifesto | Recedes or yields attention — Treatment: "look up from sentence" — **no specified DOM behavior** |

**Engineering stop:** Storyboard defines consent requirement but **not** crossing animation, duration, or which element is interactive (period vs. Story Ring vs. both). `FEATURE_01_TREATMENT.md` references period opening and circle returning. `docs/DECISIONS.md` references circular form continuity.

#### GSAP responsibilities (conditional — pending creative lock)

- Own crossing timeline once trigger fires
- Coordinate: manifesto fade/recede, period depth expansion **without** size increase (if approved), optional Story Ring reappearance
- Must not begin Emergence
- Single timeline owner — prevent duplicate triggers

#### CSS responsibilities

- Interactive target styling: **no button appearance**
- If hover state exists: "cursor near something real" — extremely subtle
- `pointer-events` scoped to intentional crossing target only
- During crossing: lock scroll if passage requires full-frame control — **requires approval** (Arrival precedent: scroll lock during transition)

#### HTML responsibilities

- Crossing target must be keyboard activatable (`<button>` visually unstyled or role="button" on period wrapper) — **must not look like button**
- `aria-label` must not instruct ("Click to enter") — violates curiosity-not-instruction
- Prefer no label; treat as part of declaration if possible — **accessibility conflict unresolved**

#### Asset requirements

- Story Ring image (`assets/noel-profile-bw.jpg`) — **only if** creative authority confirms ring reappearance at crossing
- Flag assets — **blocked** (not in storyboard)
- Passage texture/void — **not specified**

#### Audio requirements

- No instructive chime
- Optional ambient begin at crossing — **not authorized in storyboard** — blocked

#### Accessibility considerations

- **Critical conflict:** consent crossing requires discoverable interaction; Treatment forbids obvious UI and instruction
- Keyboard path mandatory — but labeling cannot say "Enter"
- `prefers-reduced-motion`: crossing must complete without disorientation — provide reduced passage variant
- Focus management during crossing — TBD

#### Responsive considerations

- Touch target size vs. period size — see Shot 003
- Crossing must work on touch devices without hover

#### Open questions — engineering stop

1. **Interaction target:** period, Story Ring, or composite — not resolved in storyboard.
2. **Crossing animation specification:** absent — cannot implement passage visual.
3. **Accessibility vs. curiosity:** unresolved without creative decision on focusable period and labeling strategy.

---

### Shot 005 — Between

**Storyboard authority:** Darkness. No horizon. No orientation. No destination. No up. No down. Remembered world ended. Next world not begun. Purpose: passage.

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
| Period / ring | Not identifiable |
| Navigation | Part Two: remains available — must be nearly invisible against dark field or ethically receded; **visual spec conflict: nav text on pure black**
| Geography | **Absent** |
| Horizon / stars / river | **Absent** |

#### Animation timeline

| Phase | Behavior |
|---|---|
| Entry | Fade or cut to featureless dark — **method not specified** |
| Hold | Passage duration undefined |
| Exit | Faintest suggestion begins (Shot 006) |

#### GSAP responsibilities

- Fade out manifesto layer
- Hold dark state
- Prepare Shot 006 subtle luminance — **must not reveal landscape**

#### CSS responsibilities

- Full-viewport dark layer `.portal-between`
- `visibility` / `opacity` on scene-02 content
- No gradients implying horizon (no vertical ground/sky split)
- Nav: maintain presence per Part Two — opacity may drop further; links remain in DOM

#### HTML responsibilities

- Portal passage container — empty or near-empty
- No `<img>` of landscape
- No text

#### Asset requirements

- None authorized

#### Audio requirements

- Silence — Treatment's distant water **not authorized here** (would identify "something beyond" as river — borderline Shot 006)

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

---

### Shot 006 — Threshold

**Storyboard authority:** Faintest suggestion another world exists. Nothing identifiable. No landscape. No Geography. Visitor senses something beyond darkness. **Portal ends here.** Purpose: anticipation.

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
- No images

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
3. **`PORTAL_TREATMENT.md` purpose line** references illuminated white screen — **not in Shot 006**. Confirm white screen is Emergence Shot 001.

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
- "PLANT YOUR FLAG" on illuminated white screen — **if** that beat belongs to Emergence per storyboard boundary
- Navigation continues per Part Two

Portal must **not**:

- Load Geography assets
- Play Geography audio
- Establish present-day home as authoritative
- Animate flag into Portal passage (DECISIONS: flag stays planted behind)

---

## 7. Production Dependencies

### Existing production files (inspected)

| File | Current Portal relevance |
|---|---|
| `index.html` | Manifesto markup; no period structure; no nav; no Portal layer |
| `css/cinema.css` | Manifesto typography + tension environment |
| `css/arrival.css` | Scene 02 shell, CSS vars for tension |
| `js/arrival.js` | Crossing into manifesto — stops at manifesto clarity |
| `js/manifesto-tension.js` | Ambient drift/breathe — **must stop before Shot 001** |
| `PERIOD-PROTOTYPE/` | Reference for impossible depth — not production |

### New modules required (names TBD from repo conventions)

| Module | Responsibility |
|---|---|
| `js/portal.js` (or equivalent) | Shot state machine, timers, crossing consent, Emergence handoff |
| `css/portal.css` | Between, Threshold, period depth (black field) |
| Navigation markup + styles | Part Two desktop nav |

### GSAP usage summary

| Shot | GSAP |
|---|---|
| 001 | Pause/kill tension tweens |
| 002 | None |
| 003 | Optional — prefer CSS keyframes |
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
| 001 | Zero visible motion 3s; tension off |
| 002 | No visible DOM change from 001 |
| 003 | Depth inside period; size/brightness/glow unchanged |
| 004 | Crossing only on visitor action |
| 005 | No horizon, no Geography, no orientation |
| 006 | Suggestion only; nothing identifiable; Portal ends |

---

## 9. Consolidated Open Questions (Implementation Blockers)

Questions must be answered in creative authority — not inferred by engineering.

### Resolved (v1.1 — Continuous Experience Architecture)

- ~~11. Flag beat placement relative to storyboard~~
- ~~5. Crossing interaction target: period, Story Ring, or both~~ → **NC monogram**
- ~~12. Navigation timing conflict across governing documents~~
- ~~14. Whether Story Ring reappears during Portal shots~~ → **continuous social world; not Portal symbol**
- ~~4. Period touch target vs. "not larger" on mobile~~ → **N/A**

### Open — requires storyboard reconciliation

1. **Storyboard shots 001–006 vs. monogram Portal symbol** — shots authored for period threshold; monogram is now Portal entry  
2. Shot 002 duration and exit condition  
3. Shot 003 entry trigger (if retained for monogram)  
4. Period depth animation duration — **N/A unless storyboard retains period beats**  
5. Accessibility strategy for wordless consent threshold — **applies to monogram**  
6. Crossing animation specification and duration  
7. Shot 005 passage duration  
8. Nav visibility floor during Between/Threshold darkness  
9. Shot 006 suggestion mechanism and hold duration  
10. White screen / Emergence ownership confirmation    

---

## 10. Engineering Principles for This Feature

1. **Implement only what is approved.** Where authority is silent, stop and ask.
2. **Do not redesign shots** for convenience.
3. **Portal never reveals Geography.**
4. **Crossing is consent.**
5. **Period is never larger, brighter, or glowing** (Shots 001–003).
6. **Navigation serves experience; experience never serves navigation** (`PORTAL_TREATMENT.md` Permanent Navigation Rule).
7. **Prototype ≠ production** — `PERIOD-PROTOTYPE/` informs depth mechanism only; black manifesto is production context.

---

*End of Portal Engineering Plan — Version 1.0.*
