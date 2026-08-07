# Decisions

## Permanent design principles

The visitor should not remember how new writing appeared. They should remember that it appeared.

Narrative pacing should be described in human experience terms (for example, "one natural reading breath") rather than fixed durations whenever practical. Engineering may translate those decisions into timing values during implementation.

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

Scroll behavior during Task 007 remains an open decision until experienced in the browser.

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