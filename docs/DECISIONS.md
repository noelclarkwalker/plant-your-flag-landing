# Decisions

## Feature 01 — Arrival

### Auto-open delay

Auto-open delay is 6000 ms. This value was chosen after user testing to allow visitors enough time to naturally read and absorb Scene 2 before the automatic story begins.

### Story Ring after invitation

The Story Ring is not discarded after the invitation is accepted. It remains as a symbolic object until it transforms into the portal/iris.

In Task 006, the ring stops inviting, completes one final gentle breath, and becomes completely still. It is not removed, scaled, shrunk, or moved.

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
