# Decisions

## Permanent Design Principles

The visitor should not remember how new writing appeared. They should remember that it appeared.

Narrative pacing should be described in human experience terms whenever practical. Technical translation may occur later without redefining the creative decision.

Environmental storytelling should be discovered, not introduced.

Objects should feel noticed rather than placed.

The visitor should feel they uncovered something that was quietly waiting for them.

### Presence Before Notice

Important narrative elements should exist compositionally before the visitor consciously notices them.

The environment should already feel complete before an individual narrative beat becomes the visitor's focus.

The experience reveals attention rather than creating objects for attention.

Meaning changes.

The room does not.

### Writing Leads

Writing remains the primary voice.

Reading belongs to the visitor.

Environmental storytelling supports the writing rather than interrupting it.

The experience may choreograph atmosphere, focus, and space.

It must never choreograph the act of reading.

### Memory Field and Journal

**Status:** Retired from mandatory Feature 01 Version 1.0 progression (approved governing decision).

These terms remain defined for archival reference and possible reuse elsewhere. They must not gate Manifesto or control Feature 01 progression.

**Memory Field** — historically: the environment. One stable perceptual room entered after the Crossing.

**Journal** — historically: the voice within that environment.

When these concepts appear in older treatments, storyboards, prototypes, or assets, they are archival unless explicitly re-approved for a future feature.

### Browser Silence

The browser must never become narratively visible during Feature 01 transitions.

The visitor should not perceive document growth, layout reflow, scrollbar movement, or other mechanics as part of the story.

The room remains perceptually stable.

### Legible Intentionality

**Status:** Permanent project-wide design principle (approved).

NoelClark.com may be unconventional, cinematic, experimental, and visually unexpected, but creative behavior must remain legibly intentional. A visitor may wonder what an experience means; they should not have to wonder whether the website is malfunctioning.

Detailed design authority: `DESIGN_SYSTEM.md` — Legible Intentionality.

### Borrowed Land Lifts Away

Permanent implementation principle. Engineering must preserve this in all future revisions.

Borrowed land lifts away instead of a replacement environment appearing.

During social interface surrender, only the social-media **interface** dissolves — not the writing. The Manifesto grows out of what remains.

Engineering must not use scene-load transitions, framed image presentations, or a newly loaded environment as the primary social-to-Manifesto mechanism.

The borrowed veil lifts. The writing settles into clarity. The Manifesto does not arrive as a separate destination.

---

# Feature 01 — Arrival

Feature 01 follows the approved Version 1.0 visitor spine in `docs/ROADMAP.md`.

The approved `FEATURE_01_TREATMENT.md` is the creative authority for the visitor experience.

Sequential Arrival — timed sentence delivery, staged reading beats, and site-controlled narrative presentation — is permanently superseded.

---

## Feature 01 Version 1.0 Visitor Spine (Governing)

**Status:** Permanent architectural decision
**Approved:** Resolves Creative Authority Chronology Audit conflicts

The mandatory Feature 01 visitor spine is:

1. **PLANT YOUR FLAG** — opening black field / white typography
2. **Borrowed Land**
3. **Static Social Post**
4. **Crossing** — social interface surrender / fade; leads **directly** into Manifesto
5. **Manifesto** — concludes with **“I'm planting my flag.”** (no separate perceptible room before Portal)
6. **Portal** — NC watercolor monogram threshold (`assets/images/nc-monogram-watercolor.png`); Story Ring is **not** Portal
7. **Emergence** — Geography of Curiosity; night → day → full illumination → entirely white field
8. **Closing PLANT YOUR FLAG** — black typography on white field; **distinct** from opening PLANT YOUR FLAG
9. **Wander** — free exploration of NoelClark.com; does not automatically require another cinematic scene

**Retired from mandatory progression** (archival; may not gate Manifesto):

Memory Field · Journal · Wayback Memory · Typewriter · Memory Cascade · Expanding Room · separate Planting My Flag state

Associated creative material, lines, assets, and prototypes may be retained, reused elsewhere, or removed after review. They must not control Feature 01 progression.

---

## Static Social Post

The social post begins in deliberately familiar borrowed language.

The opening line is:

“There was a time when the internet felt like wandering into someone’s world...”

The glowing circle surrounding the profile photograph is the invitation.

The visitor may accept the invitation or continue reading.

Two approved progression paths exist from the Static Social Post:

1. **Visitor activation** — intentional activation of the invitation begins Crossing immediately.
2. **Idle continuation** — if the visitor does not activate the invitation, Crossing may begin automatically after an approved observation/read interval that preserves time to encounter and read the Social Post while maintaining cinematic forward movement.

Visitor activation always takes precedence. Only one Crossing may begin. Any pending idle continuation must be cancelled once Crossing begins. The exact observation/read interval is **unresolved** and is not locked by this decision.

Nobody misses the complete first-time journey.

---

## Crossing

The Crossing begins when the Static Social Post exit path is satisfied — through intentional visitor activation of the invitation or approved idle continuation.

The social interface surrenders and fades — not the writing.

The visitor is carried directly into the Manifesto.

The glow surrounding the profile photograph completes its role as invitation. It is not Portal.

Crossing engineering: the borrowed social interface dissolves. The Manifesto remains. See **Borrowed Land Lifts Away**.

---

## Memory Field

**Status:** Retired from mandatory Feature 01 Version 1.0 progression.

The following is archival reference only. It must not gate Manifesto or control Feature 01 progression.

The environment after the Crossing was historically remembered NoelClark.com — not present-day NoelClark.com.

The remembered environment was to remain perceptually stable while the visitor read and discovered memory.

Memory Field engineering (archival): full-bleed remembered environment beneath a borrowed veil. See **Borrowed Land Lifts Away** for the current social-to-Manifesto principle.

---

## Journal

**Status:** Retired from mandatory Feature 01 Version 1.0 progression.

The following is archival reference only.

The Journal was historically the voice within remembered NoelClark.com.

Approved opening lines (archival — may be reused elsewhere):

1. “There was a time when the internet felt like wandering into someone’s world...”
2. “NoelClark.com has been my home on the internet since 2010.”
3. “It was just me and my open journal...”

Line 1 may appear on the Static Social Post. The full three-line Journal sequence is not mandatory before Manifesto.

---

## Wayback Memory

**Status:** Retired from mandatory Feature 01 Version 1.0 progression.

The following is archival reference only.

The first Wayback memory asset remains approved for possible reuse:

`assets/images/wayback-01.jpg`

Approved alt text:

“An early NoelClark.com homepage screen from 2012 declaring purpose to honor late father's life and help others.”

Wayback photographs are memory objects — not gallery images, hero images, or decorative illustrations.

---

## Typewriter and Memory Cascade

**Status:** Retired from mandatory Feature 01 Version 1.0 progression.

The following is archival reference only.

The typewriter treatment was reserved for:

“One day I’m writing about grief...”

Memory Cascade imagery (including deer and seemingly unrelated coexistence in remembered-home context) was archival creative material.

**Deer and natural coexistence in the Geography of Curiosity belong to Emergence**, not to mandatory pre-Manifesto progression.

---

## Expansion

**Status:** Retired from mandatory Feature 01 Version 1.0 progression.

Environmental expansion within the remembered environment was archival creative material. It does not control Feature 01 progression.

---

## Manifesto

The Manifesto begins when the social interface surrenders and fades.

It is one continuous editorial composition through the Manifesto conclusion.

The Static Social Post bridge sentence carries the opening line with ellipsis. The revealed declaration continues from the second thought and must not duplicate that opening line in markup.

The Manifesto concludes with the approved locked copy in `MANIFESTO_TREATMENT.md` — Approved Copy (Locked).

The concluding declaration is:

"We learned to build on borrowed land."

"And borrowed land isn't where I want to build everything that matters to me."

"I'm planting my flag."

There is no separate mandatory perceptible visitor room between Manifesto and Portal.

The period at the end remains punctuation only — not Portal.

See `MANIFESTO_TREATMENT.md` for presentation authority.

---

## Planting My Flag

**Status:** Absorbed into Manifesto conclusion — not a separate mandatory Feature 01 state.

The approved locked copy is in `MANIFESTO_TREATMENT.md` — Approved Copy (Locked).

The concluding declaration is:

"We learned to build on borrowed land."

"And borrowed land isn't where I want to build everything that matters to me."

"I'm planting my flag."

is the **concluding declaration of the Manifesto experience**.

The period remains approved manifesto punctuation.

Portal passage begins from the watercolor NC monogram after the Manifesto — not from the period.

**Closing PLANT YOUR FLAG** (black typography on the Emergence white field) is a **distinct** invitation from the opening PLANT YOUR FLAG. See **Emergence**.

---

## Portal

Portal is passage.

It follows the approved Manifesto in the continuous scroll.

The visitor finishes reading the manifesto and encounters the watercolor NC monogram — Noel's signature. **The signature is the Portal symbol.** Portal begins from this mark.

**Asset (permanent):** `assets/images/nc-monogram-watercolor.png` — exact file only; no recreation or substitution.

The visitor does not enter present-day NoelClark.com before this passage.

**Superseded for Portal entry:** The circular form as Portal interaction target and Feature 01 — Portal Flag Decisions below. Story Ring remains part of the continuous social world; it is not the Portal symbol.

## Feature 01 - Portal Flag Decisions (historical — superseded for Portal entry)

The flag is a real physical object, not merely metaphor, logo, or graphic symbolism.
The flag is a white flag carrying Noèl's existing maker's mark/monogram.
The maker's mark represents continuity with the place that already existed; the flag itself represents the new 2026 decision to consciously claim that place.
The flag is beautifully made and newly raised, not weathered, distressed, antique, or artificially aged.
The declaration does not transform into the flag. The words remain words; the flag remains an object. Their relationship is consequence, not transformation.
The flag is already planted, upright, and flying for the first time when the visitor notices it. The visitor does not witness its planting.
The base of the flagpole is intentionally not shown. The physical ground is intentionally left undefined.
The approved flag appearance is a white moving flag bearing the black maker's mark, mounted on a smooth dark natural-wood pole with refined brass hardware, without distressed or antique treatment.
When attention finds the flag, the remembered world becomes quiet and indistinct, remaining as atmosphere rather than resolving into another identifiable scene.
The flag does not receive a new environment. The visitor's attention changes; the world does not manufacture a new destination around it.
10. The flag does not receive a new scene or destination. The remembered world remains present but relinquishes attention, softening into atmosphere rather than becoming another identifiable environment.
---

## Emergence

Emergence is arrival into the Geography of Curiosity.

Portal and Emergence are distinct.

**Portal = passage.**

**Emergence = arrival.**

Emergence begins when the world beyond Portal first becomes perceptible.

It owns first light, first perception of the Geography, bounded exploration, meaningful interaction, broadened awareness, the progression from darkness through dawn into daylight, full illumination, the white arrival field, and **PLANT YOUR FLAG** as invitation.

Emergence does not create the Geography as the visitor progresses.

The Geography already exists beyond what the visitor can presently perceive.

Awareness broadens perception of that existing world.

The visitor should feel that they arrived somewhere that existed before they could see it.

---

## Wander

Wander begins after Feature 01 Arrival completes — after Emergence and the closing PLANT YOUR FLAG handoff.

The visitor is free to explore NoelClark.com.

Wander does not automatically require another cinematic scene.

The website has finished leading.

Navigation's emotional role belongs to Wander even though navigation is physically available from initial arrival onward.

**Note:** Navigation **presence** (upper-right, from initial arrival onward) is governed by `PORTAL_TREATMENT.md` Part Two and the Continuous Experience Architecture decision below.

---

## Continuous Experience Architecture (Version 1.0)

**Status:** Permanent architectural decision — governing Feature 01 visitor spine
**Scope:** Flow and sequence; approved visuals remain intact unless separately amended

This section records the approved Version 1.0 Continuous Experience architecture. It supersedes any earlier mandatory Memory Field → Journal → … → Manifesto progression.

### Approved visitor spine

```
PLANT YOUR FLAG
  ↓
Borrowed Land
  ↓
Static Social Post
  ↓
Crossing (social interface surrender / fade)
  ↓
Manifesto (concludes with “I'm planting my flag.”)
  ↓
Portal (NC watercolor monogram)
  ↓
Emergence / Geography of Curiosity (night → day → white field)
  ↓
Closing PLANT YOUR FLAG (black on white — distinct from opening)
  ↓
Wander
```

There are no page transitions anywhere in this sequence.

### Production baseline — Manifesto

The current approved Manifesto presentation is the production baseline.

Preserve exactly:

- typography, font, font sizes
- spacing, margins
- manifesto copy
- black background
- hierarchy, composition, pacing
- approved visual appearance

### Continuous scroll — no page transitions

Hero, remembered social world, Manifesto, Portal, Emergence, and Wander belong to **one continuous scrolling experience** without perceptible layout resets.

### Story Ring

The Story Ring is part of the continuous social world. It is the invitation — **not** Portal.

The social world continues naturally until the visitor activates the invitation or approved idle continuation begins Crossing.

### Transition into the Manifesto

Initiated after intentional visitor activation of the invitation or approved idle continuation from the Static Social Post. Both paths use the same authoritative Crossing entry. Exact idle interval: **unresolved**.

When triggered:

- The social-media **interface** begins dissolving — not the words
- Avatars, notifications, reactions, buttons, cards, interface chrome, floating UI dissolve
- The social world quietly dies
- The Manifesto remains
- The visitor realizes the words were always the point

### Portal symbol (permanent)

**Asset:** `assets/images/nc-monogram-watercolor.png`

- Use this exact uploaded asset
- Do not recreate, redraw, restyle, or substitute

The visitor finishes reading the Manifesto. The **final object they encounter** is the watercolor NC monogram — Noel's signature. **The signature becomes the Portal.** The Portal begins from this mark.

The period after “I'm planting my flag.” remains punctuation only.

### Emergence and closing PLANT YOUR FLAG

Emergence first reveals the Geography of Curiosity after Portal passage.

Emergence progresses from night through dawn into daylight and full illumination until the scene completes into an **entirely white field**.

**Closing PLANT YOUR FLAG** appears in black typography on that white field. It is **distinct** from the opening PLANT YOUR FLAG.

See `EMERGENCE_TREATMENT.md` for creative authority.

### Navigation (unchanged from Part Two)

Navigation is globally available from initial arrival onward.

Upper-right. Subtle. No attention-seeking animation, glow, pulse, or visual competition. Visitors may leave at any time.

Feature 01 states do not own navigation visibility.

### Do not change (unless required for this flow)

Do not redesign Hero, Story Ring, Manifesto, or Portal. Do not change spacing, typography, colors, or approved composition unless separately approved.

---

## Permanent Architectural Order

The following relationships are resolved for Version 1.0:

* Social interface surrender leads **directly** into Manifesto.
* **“I'm planting my flag.”** is the Manifesto conclusion — not a separate mandatory room or state.
* Manifesto precedes Portal.
* The watercolor NC monogram (`assets/images/nc-monogram-watercolor.png`) is the Portal symbol.
* The Story Ring is **not** Portal.
* The period is punctuation only — **not** Portal.
* Portal is passage; Emergence is arrival into the Geography of Curiosity.
* Deer and natural coexistence belong to Emergence / Geography — not mandatory pre-Manifesto progression.
* Emergence: night → day → entirely white field → closing PLANT YOUR FLAG.
* Opening PLANT YOUR FLAG and closing PLANT YOUR FLAG are **distinct**.
* Wander follows Arrival; it does not automatically require another cinematic scene.
* Hero → social world → Manifesto → Portal → Emergence → closing PLANT YOUR FLAG → Wander is one continuous scroll — no page transitions.
* Navigation is globally available from initial arrival onward (per `PORTAL_TREATMENT.md` Part Two).
* Memory Field, Journal, Wayback Memory, Typewriter, Memory Cascade, Expanding Room, and separate Planting My Flag state are **retired from mandatory Feature 01 progression** (archival only).
