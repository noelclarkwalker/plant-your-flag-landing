# Decisions

## Permanent design principles

The visitor should not remember how new writing appeared. They should remember that it appeared.

Narrative pacing should be described in human experience terms (for example, "one natural reading breath") rather than fixed durations whenever practical. Engineering may translate those decisions into timing values during implementation.

Environmental storytelling should be discovered, not introduced. Objects should feel noticed rather than placed. The visitor should feel they uncovered something that was quietly waiting for them.

### Presence before notice

Important narrative elements should exist compositionally before the visitor consciously notices them.

The scene should already be compositionally complete before a narrative beat begins. Elements occupy their final positions within the layout before the visitor consciously notices them.

The experience should reveal attention, not create objects. Task-level transitions change perception, not layout.

---

## Feature 01 — Arrival

### Auto-open delay

Auto-open delay is 6000 ms. This value was chosen after user testing to allow visitors enough time to naturally read and absorb Scene 2 before the automatic story begins.

### Story Ring after invitation

The Story Ring is not discarded after the invitation is accepted. It remains as a symbolic object until it transforms into the portal/iris.

In Task 006, the ring stops inviting, completes one final gentle breath, and becomes completely still. It is not removed, scaled, shrunk, or moved.

In Task 007 and beyond, the ring remains exactly where it is. Do not move it, resize it, or animate it. Its stillness is symbolic.

### Task 006 — Social dissolve timings

Total sequence duration: **4400 ms** (200 + 800 + 1200 + 700 + 1500).

| Beat | Duration | Description |
|---|---|---|
| 0 | 200 ms | Threshold stillness |
| 2 | 800 ms | Profile fade |
| 3 | 1200 ms | Card surface softening |
| 4 | 700 ms | Ring final breath, then stillness |
| 5 | 1500 ms | Writing holds |

Easing: smooth cinematic (`power2.inOut` for dissolve; `sine.inOut` for ring breath).

### Task 006 — Scroll lock

Page scrolling locks when the arrival transition begins and returns when the 4400 ms sequence completes.

Arrival-specific CSS for Feature 01 lives in `css/social.css` under the Task 006 section until Feature 01 is complete.

### Task 007 — Second sentence

The second sentence is:

"NoelClark.com has been my home on the internet since 2010."

It should feel discovered rather than announced. The visitor should feel they quietly noticed the continuation of the same voice — not that the website presented new content.

Maintain identical typography, voice, visual weight, and hierarchy. No dramatic entrance. No visual emphasis. The second sentence continues the first thought; it is not a new section.

Task 007 Beat 1 pacing: one natural reading breath after Task 006 ends (engineering determines exact timing during implementation).

The hold after both sentences are present should be slightly longer than the one-line hold from Task 006 (1500 ms). The visitor has just realized this is someone's home — that realization deserves a little more space (exact duration determined during implementation).

### Task 007 — Implementation timings

Engineering translation of human pacing terms (tunable during browser review):

| Beat | Duration | Description |
|---|---|---|
| 1 | 900 ms | One natural reading breath after Task 006 |
| 2 | 1200 ms | Voice continuation (opacity only) |
| 4 | 2000 ms | Hold after both sentences present |

Markup: both sentences live inside `.narrative-voice` as sibling `.social-text` paragraphs — one continuous voice, identical typography.

Task 007

Scroll remains effectively paused through the narrative continuation.

User testing showed visitors naturally attempt to scroll at this moment, but the continuation arrives before the interaction feels interrupted.

The narrative takes precedence over immediate scrolling during this brief transition.

### Task 008 — First home experience

Task 008 answers: "What is the first experience someone has after they have truly entered this home?"

The third sentence is exactly:

"It was just me and my open journal..."

It arrives with the same discovery principle as Task 007 — identical typography, voice, and visual weight. The visitor remembers the words, not the mechanism.

Task 008 is not text-only. From this point forward, the environment begins carrying part of the narrative.

Task 008 includes one Wayback photograph — not a cascade, not a gallery. It should feel like discovering an old photograph resting quietly on a desk. The image is discovered as the writing has been discovered. It does not compete with the words. Words remain the primary voice; the environment quietly begins speaking alongside them.

Scene 3 remains unchanged during Task 008.

Scroll behavior remains as established in Task 007.

Portal remains a later symbolic transformation of the Story Ring.

The current task sequence intentionally delivers the journal line and first photograph before the portal transform.

### Task 008 — Narrative choreography

The room is complete before the visitor notices it. The visitor's curiosity, not the website's timing, determines the order of discovery.

Begins immediately after Task 007 ends. Two opening sentences and a still ring. The visitor feels inside NoelClark.com.

| Beat | Name | Summary |
|---|---|---|
| 0 | The Afterglow of Recognition | Nothing new; arrival tension loosens |
| 1 | One Natural Reading Breath | Stillness; the home does not rush |
| 2 | The Home Names Itself | Third sentence discovered: "It was just me and my open journal..." |
| 3 | The Home Begins to Reveal Itself | Environment joins the narrative for the first time — quiet, personal, inhabited |
| 4 | The First Photograph | One Wayback image discovered like writing — resting on a desk, not competing with words |
| 5 | Words and Memory Together | Journal voice and one memory object share the same quiet space |
| 6 | The Hold | Hold on three sentences + one photograph; duration in human terms until browser review |

Task 008 ends when the third sentence and one photograph have settled, the ring remains still, Scene 3 is unchanged, and the hold completes.

Task 008 changes perception, not layout. The third sentence and `wayback-01.jpg` already occupy their final compositional positions before Task 008 begins. Task 008 choreographs attention migrating to what is already there — not objects being introduced.

### Task 008 — First memory object

The first Wayback photograph is:

`assets/images/wayback-01.jpg`

`wayback-01.jpg` is the first memory object.

Wayback photographs are memory objects, not gallery images, hero images, or decorative illustrations.

Each photograph is discovered individually and carries narrative meaning.

The first photograph establishes the emotional language for every memory object that follows.

The first Wayback photograph was chosen because it best communicates that this home has a long memory, not necessarily because it is the oldest screenshot.

The browser must never comment on this crossing.

The visitor should experience one stable environment throughout arrival.

Meaning changes.

The room does not.


#### Open questions (Task 008)

**B. Photograph placement**

Open. Placement will be determined after experiencing the implementation in the browser.

**C. Hold duration**

Open. Continue using human experience language until implementation, then determine the exact timing during browser review. The hold should be slightly longer than Task 007’s two-line hold — more has landed.

### Task 008 — Composition vs reading experience

Composition and reading experience are separate ideas.

The memory scene is compositionally complete before Task 008 begins: line three and the first photograph occupy their final positions, hierarchy, and spacing. Task 007 preserves the emotional experience of a **two-line hold** — the visitor is not invited to read further yet.

`prepareMemoryScene()` runs after the second sentence arrives. It prepares the narrative environment where memory already exists. Line three and the photograph are fully laid out beneath a clip that preserves the two-line reading frame.

Task 008 begins with `inviteMemoryAttention()` — the clip releases. Layout does not change. The visitor’s attention may continue downward into what was already there. Task 008 choreographs **time and stillness**, not opacity reveals.

Eye choreography, not reveal choreography. Opacity and blur are not the primary devices for line three or the first memory object.

### Task 008 — Implementation timings

Prior implementation used opacity 0 → 1 reveals, which caused a “plop” feeling. Current approach uses compositional clip + pacing; timings remain tunable during browser review.

| Beat | Duration | Description |
|---|---|---|
| — | — | `inviteMemoryAttention()` — clip releases; composition unchanged |
| 0 | 400 ms | Afterglow after Task 007 |
| 1 | 900 ms | One natural reading breath |
| 2–4 | 1800 ms | Attention continues downward — unified stillness |
| 5 | 650 ms | Words and memory together |
| 6 | 2400 ms | Hold after three sentences + photograph |

First memory object alt text: "An early NoelClark.com homepage screen from 2012 declaring purpose to honor late father's life and help others."

Nothing should appear. Attention should migrate. The visitor discovers what was already there.

Reading belongs to the visitor.

Environment belongs to the website.

The website may choreograph atmosphere, focus, and space.

It should never choreograph the act of reading.

The browser should never reveal the choreography. The document's height should remain perceptually stable throughout the arrival experience. The scrollbar must never become part of the narrative.

Permanent Principle

The browser must never reveal the choreography.

The visitor should experience one stable environment.

Meaning changes.

The room does not.

Environmental storytelling supports the writing rather than interrupting it.

Writing remains the primary voice.

The browser must never comment on this crossing.

The visitor should experience one stable environment throughout arrival.

Meaning changes.

The room does not.