# ENGINEERING WORKFLOW
Version 1.0

This document defines how NoelClark.com is built.

---

# Principle

Every implementation begins with understanding.

Engineering exists to preserve and strengthen the approved experience.

No code should be written before the intended experience is understood.

---

# Development Cycle

## Phase 1 — Design

Design decisions are made with ChatGPT.

This includes:

- User experience
- Storytelling
- Motion
- Visual language
- Architecture
- Feature planning

Only after the experience is approved should engineering begin.

---

## Phase 2 — Engineering Review

Start a new Cursor chat only when beginning a new engineering task.

At the beginning of every new Cursor chat:

1. Read:
   - docs/CURSOR_INSTRUCTIONS.md
   - docs/MASTER_PROJECT_BRIEF.md
   - docs/DECISIONS.md
   - docs/ROADMAP.md
2. Read the files related to today's task.
3. Summarize understanding.
4. Explain the implementation plan.
5. List every file that will change.
6. Wait for approval.

No code should be generated before approval.

---

## Phase 3 — Implementation

Implement one engineering task at a time.

Keep changes as small as practical.

Prefer modifying existing code over replacing working files.

Do not combine unrelated work into one implementation.

---

## Phase 4 — Browser Review

After implementation:

- Test the browser.
- Verify animations.
- Verify responsive behavior.
- Compare against the approved experience.

---

## Phase 5 — Repository

Every completed engineering task ends with:

Test

↓

Screenshot

↓

Commit

↓

Push

GitHub remains the source of truth.

---

# Engineering Tasks

Each engineering task should produce one logical commit and one browser-testable improvement within an approved **phase**.

Phases are defined in `docs/ROADMAP.md`. Decisions live in `docs/DECISIONS.md`.

Example phases (Version 1.0 mandatory spine):

Invitation phase — Story Ring markup

Crossing phase — Social interface surrender into Manifesto

Manifesto phase — Continuous editorial composition through declaration conclusion

Portal phase — NC monogram threshold and passage

The visitor should finish Crossing feeling the social world surrendered into conviction — not that they watched an animation.


---

# AI Responsibilities

## ChatGPT

Owns:

- Design
- Architecture
- UX
- Motion
- Storytelling
- Engineering review
- Planning

## Cursor

Owns:

- Reading repository files
- Reviewing implementation
- Writing code
- Refactoring
- Showing diffs
- Debugging

Cursor should never become the design authority.

Design approval belongs to the project owner.

---

# North Star

Every engineering decision should make the visitor feel they are gradually entering another person's world.

If code accomplishes the task but weakens that feeling, the implementation should be improved.