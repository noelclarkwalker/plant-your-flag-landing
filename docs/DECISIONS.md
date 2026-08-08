# Decisions

## Permanent design principles

The visitor should not remember how new writing appeared. They should remember that it appeared.

Narrative pacing should be described in human experience terms (for example, "one natural reading breath") rather than fixed durations whenever practical. Engineering may translate those decisions into timing values during implementation.

Environmental storytelling should be discovered, not introduced. Objects should feel noticed rather than placed. The visitor should feel they uncovered something that was quietly waiting for them.

### Presence before notice

Important narrative elements should exist compositionally before the visitor consciously notices them.

The scene should already be compositionally complete before a narrative beat begins. Elements occupy their final positions within the layout before the visitor consciously notices them.

The experience should reveal attention, not create objects. Phase transitions change perception, not layout.

### Memory Field and Journal

These terms are permanently resolved:

**Memory Field** — the environment. One stable perceptual room the visitor enters after the Crossing. The Memory Field carries the emotional atmosphere of NoelClark.com before algorithms, feeds, and platform performance.

**Journal** — the voice within that environment. The writing that leads. Memory objects quietly support the Journal; they do not compete with it.

The visitor reads the Journal inside the Memory Field. They do not wait for the website to reveal the next step.

### Browser silence

The browser must never become narratively visible during the **Crossing** or the **Memory Field**.

The visitor should not perceive scrollbar movement, document growth, layout reflow, or any browser chrome commenting on the story. The document height should remain perceptually stable throughout both phases. The scrollbar must never become part of the narrative.

Meaning changes. The room does not.

Reading belongs to the visitor. Writing remains the primary voice. Environmental storytelling supports the writing rather than interrupting it.

The website may choreograph atmosphere, focus, and space. It should never choreograph the act of reading.

---

## Feature 01 — Arrival

Feature 01 follows the phase sequence in `docs/ROADMAP.md`. Engineering tasks exist to build approved phases; they do not define the experience.

Sequential Arrival — timed sentence delivery, staged reading beats, and site-controlled narrative presentation — is **superseded**. Do not implement from that model.

---

### Invitation phase — Story Ring

Auto-open delay is **6000 ms**. This value was chosen after user testing to allow visitors enough time to naturally read and absorb the Static Social Post before the story begins automatically.

The Story Ring is the invitation. Click or wait — nobody misses the experience.

The Story Ring is not discarded after the invitation is accepted. It remains one symbolic object whose meaning evolves: **invitation → stillness → portal**.

---

### Crossing phase — Feed Dissolves

The Crossing begins when the visitor accepts the invitation. Platform language falls away — profile, card surface, social chrome. The visitor crosses from borrowed land into NoelClark.com.

During the Crossing, the Story Ring stops inviting, completes one final gentle breath, and becomes completely still. It is not removed, scaled, shrunk, or moved.

**Crossing timings** (engineering translation; tunable during browser review):

Total sequence duration: **4400 ms** (200 + 800 + 1200 + 700 + 1500).

| Beat | Duration | Description |
|---|---|---|
| 0 | 200 ms | Threshold stillness |
| 2 | 800 ms | Profile fade |
| 3 | 1200 ms | Card surface softening |
| 4 | 700 ms | Ring final breath, then stillness |
| 5 | 1500 ms | Writing holds |

Easing: smooth cinematic (`power2.inOut` for dissolve; `sine.inOut` for ring breath).

The visitor should finish the Crossing feeling they crossed a threshold — not that they watched an animation.

---

### Memory Field phase

After the Crossing, the visitor enters the Memory Field — not another social card.

The Memory Field is perceptually stable. The browser must remain silent. One room. No sense that the page is assembling itself.

Through the Memory Field and Journal phases, the Story Ring remains exactly where it is. Do not move it, resize it, or animate it. Its stillness is symbolic.

---

### Journal phase — opening voice

The Journal is the voice within the Memory Field. It carries the emotional tone of NoelClark.com before algorithms.

The opening Journal voice is one continuous readable surface. The website does not present sentences on a schedule. The visitor reads at their own pace.

**Opening lines (approved copy):**

1. "There was a time when the internet felt like wandering into someone's world..."
2. "NoelClark.com has been my home on the internet since 2010."
3. "It was just me and my open journal..."

All three lines share identical typography, voice, visual weight, and hierarchy. No dramatic entrance. No visual emphasis. One voice, not three sections.

The visitor should feel they quietly noticed the continuation of the same voice — not that the website presented new content.

---

### Wayback Memory phase — first memory object

The first Wayback photograph is:

`assets/images/wayback-01.jpg`

Wayback photographs are memory objects, not gallery images, hero images, or decorative illustrations.

Each photograph is discovered individually and carries narrative meaning. The first photograph establishes the emotional language for every memory object that follows.

The first Wayback photograph was chosen because it best communicates that this home has a long memory, not necessarily because it is the oldest screenshot.

It should feel like discovering an old photograph resting quietly on a desk — found by reading, not delivered on cue. Words remain primary; the environment quietly supports them.

**First memory object alt text:** "An early NoelClark.com homepage screen from 2012 declaring purpose to honor late father's life and help others."

#### Open questions (Wayback Memory)

**Placement**

Open. Placement will be determined after experiencing the implementation in the browser.

---

### Portal phase — Story Ring transformation

The Portal phase comes **after** the Journal and first Wayback memory object have established trust. Environmental expansion (Expanding Room) also follows that trust.

During the Portal phase, the still Story Ring becomes passage. The visitor never realizes the invitation, the flag, and the portal were always the same symbolic object.

Scene 3 (modern home world) remains unchanged until the Emergence phase.

---

### Architectural order (resolved)

- The Journal experience precedes the Portal.
- The first Wayback photograph appears before the Portal transformation.
- Environmental expansion begins only after the Journal has established trust.
- The browser must remain perceptually silent throughout the Crossing and Memory Field.
- The visitor experiences one continuous crossing, one deepening, and one emergence.