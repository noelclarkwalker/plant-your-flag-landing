# MAIL ROOM BACKEND PRODUCT AUTHORITY

**Status:** APPROVED — LOCKED  
**Document:** `MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`  
**Purpose:** Canonical product authority for Mail Room submission intake, private review, selection, publication, withdrawal, and removal — backend and admin workflow only

> **This document is NOT locked merely because it exists in the repository. Human review and explicit approval are required before treating any section as locked product behavior.**

---

## Status & Jurisdiction

| Authority | Jurisdiction |
|-----------|--------------|
| **`PROJECT_CONSTITUTION.md`** | Project governance and decision discipline — **supersedes this document** |
| **`NOELCLARK_PRODUCT_MODEL.md`** | Overall product meaning, Mail Room room definition, submission ≠ selection ≠ publication, permission philosophy |
| **`css/MAIL_ROOM_TREATMENT.md`** | Approved Mail Room **visitor-facing** creative and experiential treatment |
| **`WEBSITE_VISUAL_AUTHORITY.md`** | Approved Mail Room browse/archive visual composition |
| **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** | Mail Room **backend/admin/product workflow** — submission records, review status, publication objects, withdrawal/removal tracking, private notification intent |

**This document does NOT govern:**

- visitor-facing Mail Room visual design, motion, or interaction choreography
- Mail Room frontend markup, CSS, or JavaScript
- Privacy Policy, Terms, or Mail Room Submission Terms (final legal language)
- WordPress implementation architecture (tables, endpoints, plugins)
- engineering hosting, security, or infrastructure details

**Relationship to production frontend:** The approved visitor-facing Mail Room experience in production is the **visual and interaction authority**. Future backend work must fit underneath that experience — not redesign it.

Where this document and **`NOELCLARK_PRODUCT_MODEL.md`** align on product meaning, both apply. Where **`NOELCLARK_PRODUCT_MODEL.md`** §6 uses conceptual permission language that differs from verified production visitor copy, **this document and production code control for Mail Room submission UX and backend mapping** (see §3).

---

## Document Conventions

Throughout this document, content is labeled:

- **LOCKED** — Approved product behavior established through product review. Implementation must respect it unless a higher authority is formally amended.
- **UNRESOLVED** — Deliberately open. Engineering, legal, or policy work remains. Must not be silently treated as decided.
- **LEGAL / POLICY — NOT FINALIZED** — Direction may exist, but contractual, privacy, or counsel-aligned language is not settled here.

---

## 1. PURPOSE

**LOCKED**

Establish the canonical product behavior for receiving, privately reviewing, selecting, publishing, withdrawing, and removing Mail Room submissions.

The Mail Room is **forward-facing correspondence**.

Core product laws for this workflow:

| Principle | Meaning |
|-----------|---------|
| **Submission ≠ selection** | A letter may arrive and never be selected for NoelClark.com work or release. |
| **Selection ≠ publication** | A letter may be selected for editorial consideration or creative response without the original correspondence becoming public. |
| **Nothing submitted automatically becomes public** | Successful intake creates a private record only. Publication is a separate deliberate act on a separate public object. |

**Why this exists:** The Mail Room must preserve trust in correspondence. Writers entrust material without knowing what will happen to it. The backend must reflect that uncertainty honestly — not collapse intake into publication, and not rewrite what was actually submitted when editorial work occurs later.

The backend must preserve the **already-approved visitor-facing Mail Room experience** rather than redesign it.

---

## 2. PRODUCTION SUBMISSION FLOW

**LOCKED**

Approved visitor sequence (production frontend):

1. **18+ threshold**
2. **Write Your Letter**
3. **5 Optional Questions**
4. **Sharing Permission**
5. **Private Contact**
6. **Review & Send**
7. **IT ARRIVED**

**IT ARRIVED — production rule:**

**IT ARRIVED** may appear in production **only after the server has actually accepted the submission successfully**.

A successful server acceptance creates the submission/reference record.

**No fake production success state.**

The current localhost development preview (submission stub showing that submission is not yet connected) remains **conceptually separate** from real production submission. Development preview must never be mistaken for production acceptance behavior.

**Why:** Showing success without server acceptance would misrepresent whether correspondence actually arrived. That breaks the product promise that submission means *it arrived* — not *it might arrive if we wire this later*.

---

## 3. CANONICAL SHARING VOCABULARY

**LOCKED**

The exact current production visitor-facing labels have been verified in:

`js/mail-room-submit.js`

There is **NO fourth sharing state**.

**"SHARE WITH ATTRIBUTION" is NOT canonical** and must not replace **SHARE WITH MY NAME** in visitor-facing Mail Room submission UI or in admin displays that quote what the writer saw.

Preserve the distinction between:

- **visitor-facing language** — what the writer sees and chooses
- **stored/backend value** — what systems persist and guard on
- **private admin shorthand** — terse dashboard labels for scanability

### Canonical mapping

| Visitor-facing label | Stored value | Private admin shorthand |
|----------------------|--------------|-------------------------|
| **NOT FOR PUBLIC SHARING** | `not_public` | **Not Public** |
| **SHARE ANONYMOUSLY** | `anonymous` | **Anonymous** |
| **SHARE WITH MY NAME** | `named_credit` | **Named Credit** |

**Implementation rule:** Database fields, API payloads, permission guards, and audit logs use **stored values**. Dashboard badges and filters may use **admin shorthand**. Writer-facing surfaces and any admin copy describing *what the writer chose* use **visitor-facing labels**. A mandatory mapping table connects all three — no ad hoc synonyms.

**Why dual vocabulary:** Visitor copy is relational and explicit. Admin copy must be scannable in a queue. The mapping is intentional — not drift.

---

## 4. SUBMISSION RECORD

**LOCKED**

A successfully accepted Mail Room submission creates a **private source record**.

The original submission is **IMMUTABLE** as historical source material.

It must preserve the information actually submitted, including as applicable:

- reference ID
- received date/time
- original letter body
- 18+ attestation
- answers to 0–5 optional questions
- **original submitted sharing-permission value** (`not_public` | `anonymous` | `named_credit`) — immutable historical source data
- public credit name when `named_credit` applies
- private contact email
- relevant consent/submission-term acknowledgement metadata **once legally finalized**

**LEGAL / POLICY — NOT FINALIZED:** Exact consent metadata fields and legal acknowledgement text await Submission Terms / Privacy alignment.

Do **not** silently rewrite the original submission when editorial work, excerpting, formatting, public artifact creation, or an administered pre-publication permission change occurs later.

An administered permission change does **not** overwrite the **original submitted sharing-permission value**. The original value remains preserved as historical source data. Current/operative permission for guards and publication decisions lives in the private review layer (see §7).

**Why:** What arrived and what was ultimately published are historically and editorially distinct. The writer's actual words and original permission choice at submission time are immutable source of truth for what was entrusted. Operative permission may change through administered pre-publication request — but that change is recorded separately, never by overwriting the original submission record.

---

## 5. PRIVATE EMAIL VS PUBLIC CREDIT

**LOCKED**

Private email and public attribution are **separate concepts**.

The submitter's private email:

- is administrative/private
- is not automatically public
- must **never** be substituted for public attribution

If `named_credit` is selected, publication uses the **separate public-credit field** the writer provided — not their email address.

Anonymous publication must not expose private identity or contact information.

**Why:** Accidental disclosure of private email as attribution would violate both product trust and permission intent. Structural separation in data model and admin UI reduces that risk.

---

## 6. PRIVATE MAIL ROOM NOTIFICATION

**LOCKED (product intent)**

**UNRESOLVED (delivery implementation)**

When a submission is successfully accepted, the system **should** send Noèl a private new-submission notification.

Current intended private notification destination:

`mailroomalerts@gmail.com`

This address is **operational/private** and must **NOT** be displayed to visitors.

Do **NOT** hard-code this address into visitor-facing or frontend production code.

The notification email is a **notification bell**, not the source of record.

The complete letter and optional-question responses do **not** need to be included in the notification email.

Preferred notification content is minimal, such as:

- a new Mail Room submission arrived
- reference ID
- secure/private admin link when available

The actual submission lives in the secure backend / private WordPress administration system.

**Why:** Email is an alert channel. The authoritative record must live in the secured admin system so review, status, and publication workflow have a single source of truth.

---

## 7. PRIVATE REVIEW LAYER

**LOCKED**

Private review metadata is **separate** from the immutable submission.

The private review layer may contain:

- editorial status (see §8)
- Noèl's private notes
- **current / operative sharing permission** — used for permission guards and publication decisions; initially matches the original submitted value; may change through an administered pre-publication permission-change request without overwriting the immutable submission record
- **permission-change history** — original submitted permission, current operative permission, request/change history, and relevant dates as applicable (see §13, §17)
- withdrawal/removal request information
- connection to any resulting public artifact

**Private Notes** are backstage only.

They must **never** automatically become visitor-facing content.

**Why:** Noèl needs a place to preserve future connections, editorial thoughts, potential P.S. relationships, publication ideas, and reasons for reconsideration **without altering the writer's original submission**. Notes support editorial memory across months or years — especially when correspondence is NOT SELECTED today but may become relevant later.

---

## 8. EDITORIAL STATUS MODEL

**LOCKED**

Canonical editorial statuses:

| Status | Meaning |
|--------|---------|
| **NEW** | Successfully received but not yet reviewed. |
| **REVIEWED** | Noèl has reviewed the submission and it is under editorial consideration. |
| **SELECTED** | Noèl has chosen it for active potential Mail Room / publication work. **SELECTED is NOT publication.** |
| **NOT SELECTED** | Noèl has decided not to select it at this time. **NOT SELECTED is NOT permanent rejection.** |
| **PUBLISHED** | A public Mail Room artifact has actually been published from / connected to the submission. **PUBLISHED is terminal as an editorial-history status.** |

**Why NOT SELECTED is not permanent rejection:** Correspondence that is not relevant today may become creatively or editorially relevant months or years later. The archive must support indefinite reconsideration.

**Why PUBLISHED is terminal (editorial history):** Once publication occurred, that historical fact must remain auditable even if the public artifact is later withdrawn or removed (see §12).

---

## 9. ALLOWED EDITORIAL TRANSITIONS

**LOCKED**

### Allowed transitions

```
NEW → REVIEWED

REVIEWED → SELECTED
REVIEWED → NOT SELECTED

SELECTED → REVIEWED
SELECTED → NOT SELECTED
SELECTED → PUBLISHED

NOT SELECTED → REVIEWED
```

### Forbidden transitions

- **NEW → NOT SELECTED** — Cannot pass on correspondence not yet reviewed.
- **NEW → SELECTED** — Must pass through review.
- **NEW → PUBLISHED** — Publication requires selection and deliberate editorial action.
- **NOT SELECTED → SELECTED** — Reconsideration must return through **REVIEWED** first. REVIEWED is the canonical consideration checkpoint.
- **NOT SELECTED → PUBLISHED** — Must pass through SELECTED (and permission guards).
- **Any → NEW** — Submissions do not "un-arrive."

### Rationale for specific transitions

| Transition | Why it exists |
|------------|---------------|
| **SELECTED → REVIEWED** | Noèl may cease active editorial work without deciding against the submission. It returns to consideration. |
| **SELECTED → NOT SELECTED** | Noèl may begin working with a selected submission and later make a definite decision not to proceed. Direct path is intentional — do not force SELECTED → REVIEWED → NOT SELECTED when the decision is already clear. |
| **NOT SELECTED → REVIEWED** (only) | Supports indefinite reconsideration. Notes like "revisit for future project" imply reopening consideration — not a dead workflow. |
| **No NOT SELECTED → SELECTED** | Forces explicit re-entry through the consideration checkpoint so selection is never accidental after a pass. |

Admin UI must only offer valid next states from the current state.

---

## 10. PUBLIC ARTIFACT IS A SEPARATE OBJECT

**LOCKED**

Publication must **NOT** mutate the original submission into the public object.

Architecture:

```
IMMUTABLE SUBMISSION
  → PRIVATE REVIEW / EDITORIAL LAYER
    → SEPARATE PUBLIC MAIL ROOM ARTIFACT
```

The public artifact may contain approved editorial presentation, formatting, excerpts, attribution treatment, or other authorized publication choices.

The original submission remains preserved separately.

**Why:** "What arrived" and "what was ultimately published" are historically and editorially distinct things. Excerpting, formatting, and presentation choices belong on the public artifact — not as silent overwrites of the private source record.

**UNRESOLVED:** Exact WordPress content model for public artifacts (custom post type vs custom table vs hybrid) — see §21.

---

## 11. PERMISSION GUARDS

**LOCKED**

The system should enforce relevant permission guards rather than relying solely on Noèl remembering them during publication.

### `not_public` / NOT FOR PUBLIC SHARING

The actual letter, sender identity, quotations, or protected submitted material must **not** be pushed through the normal public-publication pipeline.

This permission does **NOT** attempt to prohibit Noèl's independent human inspiration, research, thought, or original creative response arising from correspondence.

**Why:** Permission governs use of the writer's **actual submission material** — not Noèl's independent creative life.

### `anonymous` / SHARE ANONYMOUSLY

Authorized public use must **not** identify the sender.

Private email / contact information remains private.

### `named_credit` / SHARE WITH MY NAME

Authorized public use may credit the sender using the **separate public-credit name** they provided.

Private email remains private and is **never** used as attribution.

**Implementation expectation:** When `not_public`, no admin action that creates or publishes a WHAT ARRIVED artifact from this submission should be available. Hard-block at the action level — not a reminder label alone.

---

## 12. PUBLIC ARTIFACT AVAILABILITY STATE

**LOCKED**

Editorial history and current public availability are **different dimensions**.

A **PUBLISHED** submission remains editorially **PUBLISHED** even if its public artifact later comes down.

Canonical public-artifact availability states:

| State | Meaning |
|-------|---------|
| **LIVE** | Artifact is currently publicly available. |
| **WITHDRAWN** | Artifact was taken down following the submitter's withdrawal/removal request. |
| **REMOVED** | Artifact was taken down administratively/editorially for another reason. |

Do **NOT** roll **PUBLISHED** backward merely because the public artifact is no longer live.

**Why:** The historical fact remains that the submission was selected and published. Conflating "currently live" with "was never published" would corrupt editorial history and audit trails.

---

## 13. WITHDRAWAL / REMOVAL REQUEST TRACKING

**LOCKED (tracking model)**

**LEGAL / POLICY — NOT FINALIZED (obligations and exact wording)**

Track submitter withdrawal/removal requests **separately** from both:

- editorial status
- public-artifact availability state

Conceptual request states:

| State | Meaning |
|-------|---------|
| **None** | No request recorded. |
| **Pending** | Request received; not yet handled. |
| **Handled** | Request processed according to applicable policy. |

Also preserve relevant dates, such as:

- request received
- request handled
- artifact withdrawn / removed
- permission change administered

**Permission-change administration** (pre-publication) must preserve separately:

- original submitted permission (immutable — never overwritten)
- current / operative permission
- relevant request/change history and dates as applicable

Example conceptual record (illustrates data/history separation only — exact database schema is **UNRESOLVED**):

| Field | Value |
|-------|-------|
| Original submitted permission | `anonymous` |
| Current operative permission | `not_public` |
| Permission change | administered [date] |

### Example: writer-requested outcome

| Field | Value |
|-------|-------|
| Editorial status | Published |
| Public artifact | Withdrawn |
| Withdrawal request | Handled |
| Withdrawn | [date] |

### Example: independent editorial/admin removal

| Field | Value |
|-------|-------|
| Editorial status | Published |
| Public artifact | Removed |
| Withdrawal request | None |
| Removed | [date] |

**Why:** The system should preserve what happened and why without rewriting editorial history.

Final legal wording and exact removal obligations remain subject to later Privacy / Terms / Submission Terms / counsel alignment. See **`NOELCLARK_PRODUCT_MODEL.md`** §17.11 — distinction between withdrawal of future use, removal of published correspondence, and independently inspired work **remains legally unresolved** at the Product Model level; this document establishes **tracking architecture**, not final contractual guarantees.

---

## 14. PRIVATE DASHBOARD EXPERIENCE

**LOCKED (product intent for admin UX)**

**UNRESOLVED (exact WordPress UI implementation)**

The private WordPress Mail Room should prioritize the **actual letter** rather than resemble a generic database screen.

A submission view should make it easy to understand:

- actual letter
- reference ID
- received date/time
- editorial status
- sharing permission (admin shorthand + stored value; visitor-facing label available on demand)
- public credit where applicable
- private email
- 18+ attestation
- optional-question responses
- private notes
- withdrawal/removal information
- connected public artifact and its availability state, if one exists

Public credit and private contact information should be **visually and structurally distinct** to reduce accidental disclosure.

The private inbox should eventually support practical retrieval/filtering such as:

- New / Reviewed / Selected / Not Selected / Published
- permission tier
- reference ID

Exact WordPress screen layout, block editor usage, and plugin choices are **implementation decisions** — not reasons to alter this product model.

---

## 15. OPTIONAL QUESTIONS / BACKSTAGE MAPPING

**LOCKED**

The five optional questions remain **independently optional**.

Valid submission may contain **0–5** answers.

Visitor-facing UI must **not** expose:

- NERV
- NERVprint
- STACK
- STRIKE
- SCAN
- SPONGE
- profile
- score
- result
- categorization language

The existing backstage mapping (`js/mail-room-questions-mapping.js` — **backstage only, not loaded visitor-facing**) remains private/backstage.

Do not turn backstage mapping into visitor-facing profiling language during backend implementation.

**Why:** Writers answer optional questions in their own words. Backstage interpretive mapping supports editorial reading — it is not a visitor-facing classification system.

---

## 16. PUBLICATION IS DELIBERATE

**LOCKED**

Nothing automatically publishes.

A submission can be:

- received
- reviewed
- selected

without ever becoming public.

Publication requires a **separate deliberate editorial action** creating/linking a public Mail Room artifact.

The backend must **never** treat successful submission as permission to auto-publish.

**Why:** Aligns with Product Model laws — submission ≠ selection ≠ publication. Auto-publish would collapse the correspondence trust model.

---

## 17. WITHDRAWAL BEFORE PUBLICATION

**LOCKED (product direction)**

**LEGAL / POLICY — NOT FINALIZED**

The existing product direction remains:

Before publication, a writer may request withdrawal or a sharing-permission change.

The backend architecture must be **capable of recording and administering** that request.

When a sharing-permission change is administered:

- the **original submitted permission** remains preserved in the immutable submission record
- the **current / operative permission** updates in the private review layer
- request/change history and relevant dates are preserved (see §13)

Never overwrite historical source data to reflect a permission change.

Do **not** invent final contractual or legal guarantees in this authority document.

Final wording belongs in Submission Terms / Privacy / Terms with counsel alignment.

---

## 18. CRISIS / SAFETY ARCHITECTURE

**LOCKED**

Preserve the already-approved Mail Room direction:

Difficult, dark, controversial, political, or profane correspondence is **not prohibited merely by subject matter**.

The Mail Room is **not** continuously monitored and is **not** an emergency/crisis service.

Do **not** introduce:

- clinical diagnosis
- AI crisis classification
- continuous surveillance
- location tracking
- emergency-contact collection
- DOB collection
- ID verification

The existing visitor-facing crisis note remains subordinate on Review & Send.

Do **not** redesign it in backend implementation work unless separately authorized.

**Why:** The Mail Room holds real human correspondence — including difficult material. Surveillance-style backend features would change the product's character and create false safety expectations.

---

## 19. DATA MINIMIZATION / PRODUCT BOUNDARIES

**LOCKED**

Do not collect additional personal information merely because a backend exists.

Current product direction does **NOT** require:

- home address
- phone number
- date of birth
- government ID
- emergency contact

Submission eligibility uses the existing **18+ self-attestation** model.

**Why:** Minimum necessary data supports trust and reduces exposure. Backend existence is not justification for expanded intake fields.

---

## 20. WORDPRESS RELATIONSHIP

**LOCKED (architectural intent)**

**UNRESOLVED (implementation)**

The approved frontend remains the **visual and interaction authority**.

Future WordPress implementation should provide the secure backend/admin layer underneath the approved Mail Room experience.

Do **not** rebuild the Mail Room with generic WordPress/plugin UI on the visitor-facing side.

Submission storage, admin, email notification, and publication workflow are **additive backend capabilities** — not a redesign mandate.

---

## 21. UNRESOLVED IMPLEMENTATION DECISIONS

**UNRESOLVED — NOT LOCKED BY THIS DOCUMENT**

The following require later engineering decisions. Treating any item below as locked would overstate this document's authority.

- exact WordPress data model: custom post type vs custom/private table vs hybrid
- exact endpoint technology: REST vs admin-post/AJAX or equivalent
- email delivery provider and template implementation
- spam / rate-limiting implementation
- exact reference-ID generation format
- authentication / admin capability model
- retention / deletion implementation mechanics
- backup strategy
- exact private-dashboard visual implementation in WordPress admin
- exact publication-CMS implementation for WHAT ARRIVED artifacts
- security architecture details (encryption at rest, access logging, etc.)
- final production hosting configuration
- whether optional-question backstage mapping is stored computed vs persisted
- linking model between submission record and public artifact (foreign key shape, WordPress post ID usage)

Engineering must implement these without contradicting **LOCKED** sections above.

---

## 22. LEGAL / POLICY ITEMS NOT FINALIZED HERE

**LEGAL / POLICY — NOT FINALIZED**

This authority does **NOT** finalize:

- Privacy Policy
- general Terms
- Mail Room Submission Terms
- exact retention promises
- exact deletion obligations
- final withdrawal/removal contractual language
- legal consent language and checkbox copy
- distinction between withdrawal of future use, removal of published material, and independently inspired work (**see `NOELCLARK_PRODUCT_MODEL.md` §17.11**)
- whether writers may opt into notification if material is later selected or used (**see Product Model §17.12**)

Those documents must be aligned later with the actual implemented backend mechanics and, where appropriate, counsel.

---

## 23. DECISION-RATIONALE RULE

**LOCKED**

Preserve the reasoning behind important product decisions.

Do not reduce this authority to a bare state-transition table.

Future engineers and AI assistants must be able to understand **WHY** the rules exist so they are not accidentally "simplified" away later.

In particular, preserve rationale for:

| Decision | Rationale (summary) |
|----------|---------------------|
| Immutable original submissions | Source of truth for permission, withdrawal, and history |
| Separation of submission / review / public artifact | What arrived ≠ what was published |
| Reversible NOT SELECTED | Correspondence may become relevant years later |
| SELECTED → REVIEWED | Pause active work without rejecting |
| SELECTED → NOT SELECTED | Clear decision after editorial engagement |
| No direct NOT SELECTED → SELECTED | Reconsideration must pass through REVIEWED checkpoint |
| PUBLISHED remains historical after withdrawal/removal | Editorial history ≠ current public availability |
| Separate LIVE / WITHDRAWN / REMOVED | Audit what happened and why |
| Private email vs public credit | Prevent accidental identity disclosure |
| Notification email not source of record | Secure admin system holds authoritative data |
| Submission ≠ selection ≠ publication | Core Mail Room trust model |

---

## 24. AUTHORITY / CONFLICT HANDLING

Once human-approved, this document becomes the **canonical product authority for the Mail Room backend/product workflow** within its stated jurisdiction.

It must **NOT** supersede:

- **`PROJECT_CONSTITUTION.md`**
- **`NOELCLARK_PRODUCT_MODEL.md`** for general product meaning outside Mail Room backend workflow specifics

When lower-level planning documentation contradicts this approved product model, **report the contradiction** — do not silently resolve it during implementation.

This document was created as documentation only. It does **not** automatically amend other authority documents.

### Known cross-document notes (reported at creation — not silently edited)

| Document | Note |
|----------|------|
| **`NOELCLARK_PRODUCT_MODEL.md` §6** | **Resolved.** Product Model previously used **"SHARE WITH ATTRIBUTION"** for the third permission tier; the mismatch was identified during Mail Room backend authority review. **`NOELCLARK_PRODUCT_MODEL.md` §6 has now been synchronized** to the canonical visitor-facing heading **"SHARE WITH MY NAME"** (stored value `named_credit`). There is **no longer an active contradiction**. §3 guardrail remains: **"SHARE WITH ATTRIBUTION" is NOT canonical** and must not replace **SHARE WITH MY NAME** in visitor-facing or admin copy that quotes what the writer saw. |
| **`NOELCLARK_PRODUCT_MODEL.md` §17.11–§17.12** | Revocation/removal legal distinctions and submission-use notifications remain explicitly unresolved at Product Model level. This document's withdrawal/removal **tracking model** is product architecture — not legal finalization. |
| **`css/MAIL_ROOM_TREATMENT.md`** | Correctly excludes backend/product architecture from its jurisdiction. No conflict. |
| **`PROJECT_HANDOFF_v2.0.md`** | May describe Mail Room as "not implemented" relative to an earlier repository snapshot. Production now includes approved Mail Room frontend pages. Handoff operational status should be updated separately when authorized — not silently during this task. |

---

## Appendix A — Status & Availability Quick Reference

| Dimension | Values | Notes |
|-----------|--------|-------|
| **Editorial status** | NEW, REVIEWED, SELECTED, NOT SELECTED, PUBLISHED | Submission workflow |
| **Public artifact availability** | LIVE, WITHDRAWN, REMOVED | Current public visibility |
| **Withdrawal request** | None, Pending, Handled | Separate from editorial status |
| **Sharing permission (stored)** | `not_public`, `anonymous`, `named_credit` | Original submitted value is immutable; current operative permission may change through an administered pre-publication request (see §4, §7, §13, §17) |

**PUBLISHED** (editorial) + **WITHDRAWN** or **REMOVED** (availability) is a valid and expected combination.

---

*End of document — APPROVED — LOCKED. Human review complete.*
