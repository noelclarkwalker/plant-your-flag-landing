# Feature 01 Architecture Revision

## From Sequential Arrival to Entering a Home

### Why the revision happened

Browser testing revealed that the visitor could perceive the mechanics of the website instead of simply experiencing the story.

Scrollbar movement, layout growth, and sequential presentation of new content interrupted the illusion of entering NoelClark.com.

The issue was not animation quality.

It was architecture.

Arrival should feel like entering a place, not watching a webpage assemble itself.

After the Story Ring, the site still behaved like software — presenting narrative on a schedule instead of hosting a place the visitor could read.

---

## New Principles

The visitor should never feel the website is presenting new content.

The visitor should feel they are moving deeper into an already-existing place.

Reading belongs to the visitor.

Writing remains the primary voice.

The environment supports the writing rather than interrupting it.

Meaning changes.

The room does not.

The browser must never comment on the crossing.

The browser must never become narratively visible during the **Crossing** or the **Memory Field**. Scrollbar movement, document growth, and layout reflow must not become part of the story.

---

## Memory Field and Journal

These terms are permanently resolved:

**Memory Field** — the environment. After the Crossing, the visitor enters one stable perceptual room. This is not another social card. It is the architectural container for arrival — the emotional atmosphere of NoelClark.com before algorithms, feeds, and platform performance.

**Journal** — the voice within that environment. The writing that leads. The visitor reads the Journal inside the Memory Field. Memory objects quietly support the Journal; they do not compete with it.

The visitor should feel they are reading deeper into a place that already exists, not waiting for the website to reveal the next step.

---

## Phase Sequence

Feature 01 follows `docs/ROADMAP.md`:

PLANT YOUR FLAG → Borrowed Land → Static Social Post → Feed Dissolves (Crossing) → Memory Field → Journal → Wayback Memory → Typewriter → Memory Cascade → Expanding Room → Portal → Emergence → Wander

Engineering tasks build approved phases. They do not define the experience.

---

## Architectural Decisions

The following architectural decisions are considered resolved unless intentionally revised in the future.

- **Memory Field** is the environment; **Journal** is the voice within it.
- The Journal experience precedes the Portal.
- The first Wayback photograph appears before the Portal transformation.
- Environmental expansion begins only after the Journal has established trust.
- The browser must remain perceptually silent throughout the Crossing and Memory Field.
- The visitor experiences one continuous crossing, one deepening, and one emergence.
- Sequential Arrival — timed sentence delivery and site-controlled reading beats — is superseded.

---

## Official Copy (Journal opening)

Third line of the opening Journal voice:

"It was just me and my open journal..."

---

## Story Ring (phase-based)

The Story Ring is one symbolic object across phases:

- **Invitation phase** — the ring invites; click or auto-open after 6000 ms.
- **Crossing phase** — the ring completes one final breath and becomes still.
- **Memory Field and Journal phases** — the ring remains still and unmoved; stillness is symbolic.
- **Portal phase** — the ring becomes passage.

The visitor never realizes the invitation, the flag, and the portal were always the same symbolic object.

---

## Future Implementation

Feature 01 will be implemented around this architecture rather than around individual animation tasks.

Implementation should always preserve these principles:

- Discovery, not delivery.
- One stable room during the Crossing and Memory Field.
- Writing leads.
- Environment supports.
- The browser must never become narratively visible during the Crossing or Memory Field.

No implementation decisions are made in this document.
