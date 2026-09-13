# MAIL ROOM BACKEND PRODUCT AUTHORITY

**Status:** AMENDED — LOCKED PRODUCT DECISIONS INCORPORATED — PENDING HUMAN AUTHORITY AUDIT  
**Document:** `MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`  
**Purpose:** Canonical product authority for Mail Room submission intake, private Participant records, questionnaire architecture, private review, selection, publication, withdrawal/permission-change tracking, notification, provenance, and related backend/admin workflow

> **This document is NOT locked merely because it exists in the repository. Human review and explicit approval of this amendment are required before treating the amendment as locked project behavior.**

---

## Status & Jurisdiction

| Authority | Jurisdiction |
|-----------|--------------|
| **`PROJECT_CONSTITUTION.md`** | Project governance and decision discipline — **supersedes this document** |
| **`PROJECT_HANDOFF_v2.0.md`** | Current-state operational synthesis — does **not** override this document inside Mail Room backend/admin workflow jurisdiction |
| **`NOELCLARK_PRODUCT_MODEL.md`** | Overall product meaning, Mail Room room definition, submission ≠ selection ≠ publication, permission/inspiration philosophy |
| **`css/MAIL_ROOM_TREATMENT.md`** | Approved Mail Room **visitor-facing** creative and experiential treatment |
| **`WEBSITE_VISUAL_AUTHORITY.md`** | Approved Mail Room browse/archive visual composition |
| **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** | Contact backend product behavior — **separate door / separate system** |
| **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** | Mail Room **backend/admin/product workflow** as amended here |

**This document does NOT govern:**

- visitor-facing Mail Room visual design, motion, or interaction choreography
- approved WHAT ARRIVED browse/reader creative treatment
- final enforceable Privacy Policy, general Terms, or Mail Room Submission Terms copy
- exact WordPress table/endpoint/plugin choices except where an architecture requirement is stated below
- Production deployment

**Relationship to the approved frontend:** The approved visitor-facing Mail Room experience remains the **visual and interaction authority**. Backend work must fit underneath that experience — not redesign it. Where this amendment changes **product sequence** (optional questionnaires after confirmed acceptance; success meaning), implementation must later follow this document rather than the stale pre-submission question step. That frontend change is **not** authorized by this amendment itself.

Where this document and **`NOELCLARK_PRODUCT_MODEL.md`** align on product meaning, both apply. Where they conflict **inside Mail Room backend/admin workflow**, this amended document controls for that workflow. Remaining Product Model legal-unresolved items are listed in §45 and must not be silently treated as legally finalized.

---

## Document Conventions

| Label | Meaning |
|-------|---------|
| **LOCKED PRODUCT INTENT** | Approved product meaning. Implementation must respect it. Not final enforceable legal copy. |
| **LOCKED ARCHITECTURE REQUIREMENT** | Approved system behavior/structure. Implementation must satisfy it. Exact engineering mechanism may still be an implementation decision. |
| **IMPLEMENTATION DECISION** | Engineering/hosting/UI choice not locked here. Must not contradict locked intent or architecture. |
| **COUNSEL REVIEW REQUIRED** | Product intent may exist. Exact enforceable language, operative legal mechanics, and related obligations are **not** finalized. Do not invent contract copy. |
| **FUTURE / NOT V1** | Must not be built as a V1 requirement. Architecture may leave room for it. |

Do **not** mark a product question as unresolved when this amendment has answered it.

Do **not** invent additional product decisions.

---

## 1. PURPOSE

**LOCKED PRODUCT INTENT**

Establish the canonical product behavior for receiving, privately storing, privately reviewing, selecting, publishing, tracking withdrawal/permission-change requests, associating optional questionnaires, and preserving provenance for Mail Room correspondence.

The Mail Room is **forward-facing correspondence**.

The backend must preserve the **already-approved visitor-facing Mail Room creative experience** rather than redesign it.

**Why:** Writers entrust material without knowing what will happen to it. The backend must reflect that uncertainty honestly — not collapse intake into publication, and not rewrite what was actually submitted when editorial work occurs later.

---

## 2. CORE PRODUCT LAW

**LOCKED PRODUCT INTENT**  
**LOCKED ARCHITECTURE REQUIREMENT**

**Submission ≠ selection ≠ publication.**

Successful submission means **only** that the server successfully accepted and stored the private submission and generated a real Mail Room (MR) reference ID.

Nothing automatically publishes.

Public-sharing permission means **eligibility for Noèl's editorial consideration**. It does **not** mean automatic publication, selection, response, acknowledgment, creative use, or notification.

Only Noèl, or a future administrator **explicitly authorized to make publication decisions**, may intentionally move correspondence into the public publication workflow.

A letter may arrive and never be selected. A letter may be selected and never become public. No public outcome is a legitimate outcome.

---

## 3. PRE-SUBMISSION FLOW

**LOCKED ARCHITECTURE REQUIREMENT**

The Mail Room **pre-submission** sequence is:

1. Before You Write / 18+ self-attestation
2. Write Your Letter
3. Sharing Permission
4. Private Contact
5. Review & Send
6. SEND A LETTER action

Optional questionnaires are **NOT** part of the pre-submission transaction.

They are **NOT** included in the original letter POST/accept payload.

They do **NOT** appear on Review & Send as “Answered X of N.”

**Superseded:** Any earlier authority language that placed five optional questions before SEND, treated those answers as part of the original letter submission, or used **IT ARRIVED** as the production success threshold heading.

Approved success meaning after confirmed acceptance is **“Your letter has been sent.”** (see §5).

---

## 4. REAL ACCEPTANCE

**LOCKED ARCHITECTURE REQUIREMENT**

SEND A LETTER must perform a **real server transaction**.

Success UI may appear **only** after the server confirms that the private submission has been successfully **validated and stored**.

On successful acceptance the server must:

- create the real private submission record
- create a real unique MR reference ID
- record the server acceptance timestamp
- associate the submission with a private Participant (see §6)
- record Terms version and Terms acceptance timestamp (see §23)
- return only appropriate success metadata to the visitor

If storage fails:

- there is no successful submission
- there is no real MR reference ID
- there is no success message
- the visitor must not be told the letter was received

**No fake or simulated success architecture.**  
**No DEV-SIM reference IDs.**  
**No query-parameter or localStorage success switch.**

Database storage is the success authority (see §33).

**IMPLEMENTATION DECISION:** exact endpoint technology, ID format, and persistence mechanism, provided they satisfy this section and §42.

---

## 5. SUCCESS MEANING

**LOCKED PRODUCT INTENT**

Approved success meaning:

**“Your letter has been sent.”**

That means the letter was **received into the private Mail Room only**.

It does **NOT** mean the letter was:

- read
- reviewed
- selected
- published
- responded to
- creatively used
- guaranteed any future action

After confirmed success, the visitor-facing invitation may continue:

**“Your letter has been sent. Want to go one step further?”**

See §30.

---

## 6. PRIVATE PARTICIPANT ARCHITECTURE

**LOCKED ARCHITECTURE REQUIREMENT**

Build a **private Participant layer** shared by Mail Room and NoelClark.com questionnaires.

Conceptual model:

```
Participant (P-ID)
  → zero or more MR submission records
  → zero or more Q questionnaire-response records
```

Core private participant identifying information for V1:

- name
- email
- server-generated Participant ID
- 18+ eligibility metadata where applicable

**Do NOT collect for this system in V1:**

- date of birth
- phone
- mailing address
- username/password
- social handles
- gender
- location
- other unnecessary identity fields

Email is **structurally validated** and **user-provided**. It is **unverified** in V1.

Do **NOT** automatically merge Participant records solely because names or email addresses match.

The architecture must support **future** verified identity linking/reconciliation without requiring a V1 account or identity-verification system.

**FUTURE / NOT V1:** accounts, email verification, identity proofing, automatic merge.

Participant IDs are **private infrastructure**. They are not public profiles, public handles, or visitor-facing identity.

**IMPLEMENTATION DECISION:** exact Participant table/schema and how a Mail Room Private Contact step writes/associates name + email, provided the core fields above are collected and stored privately.

---

## 7. IMMUTABLE ORIGINAL MAIL ROOM SUBMISSION

**LOCKED ARCHITECTURE REQUIREMENT**

Each successfully accepted letter receives its own MR reference ID.

The original private submission is an **immutable historical record**.

It includes the appropriate original intake facts, including:

- original letter text
- original sharing choice
- Public Credit if applicable
- private Participant relationship / contact information
- 18+ attestation metadata
- accepted Mail Room Submission Terms version
- Terms acceptance timestamp
- server acceptance timestamp
- unique MR reference ID
- required provenance metadata (see §22)

**Do NOT include** post-submission questionnaire answers inside the immutable original letter record.

Later events must **not** overwrite historical facts.

Permission-change requests, withdrawal/removal requests, editorial status, notes, publication relationships, questionnaire associations, and similar later events belong in appropriate **separate** history/review/questionnaire layers.

**Why:** What arrived and what was later done with it are historically distinct. The writer's actual words and original choices at acceptance time are the source of truth for what was entrusted.

---

## 8. CANONICAL SHARING VOCABULARY

**LOCKED PRODUCT INTENT**  
**LOCKED ARCHITECTURE REQUIREMENT**

There are **exactly three** Mail Room sharing choices. There is **no fourth**.

**“SHARE WITH ATTRIBUTION” is NOT canonical** and must not replace **SHARE WITH MY NAME** in visitor-facing Mail Room submission UI or in admin displays that quote what the writer saw.

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

Database fields, API payloads, permission guards, and audit logs use **stored values**. Dashboard badges and filters may use **admin shorthand**. Writer-facing surfaces and any admin copy describing *what the writer chose* use **visitor-facing labels**. A mandatory mapping table connects all three — no ad hoc synonyms.

---

## 9. SHARING CHOICES

**LOCKED PRODUCT INTENT**  
**LOCKED ARCHITECTURE REQUIREMENT**

Public-sharing permission is **eligibility for editorial consideration**, not a publication command.

### 9.1 NOT FOR PUBLIC SHARING (`not_public`)

The actual correspondence is **not eligible** for:

- public sharing
- WHAT ARRIVED publication
- quotation as source material
- inclusion of the actual letter in a commercial Mail Room publication

Independent inspiration is separate. Encountering private correspondence does **not** prohibit Noèl from independently thinking, observing themes/patterns, or creating original work, **provided protected expression from the private letter is not publicly reproduced contrary to the writer's choice**.

A `not_public` submission **must not** be capable of becoming public merely because a WordPress visibility switch was changed. Permission guards must exist between private records and the public publication workflow (see §19).

### 9.2 SHARE ANONYMOUSLY (`anonymous`)

Makes the correspondence **eligible** for Noèl's editorial consideration and possible public use **without identifying the writer**.

Noèl may redact or generalize identifying details as necessary to preserve anonymity.

The immutable private original remains unchanged.

Private email / Participant identity remain private.

### 9.3 SHARE WITH MY NAME (`named_credit`)

Makes the correspondence **eligible** for Noèl's editorial consideration and possible public use using the separately supplied **Public Credit**.

Private identity/email must **never** be substituted for Public Credit.

Public Credit is reproduced **exactly as submitted**, including spelling, capitalization, punctuation, spacing, and apparent errors.

A later requested Public Credit change may be considered at Noèl's discretion. The historical original Public Credit remains unchanged.

---

## 10. PRIVATE EMAIL AND PUBLIC CREDIT

**LOCKED PRODUCT INTENT**

Private email and public attribution are **separate concepts**.

Private email may be used for **legitimate Mail Room administration** related to the submission, including:

- handling submission issues
- permission / withdrawal requests
- optional notification if Noèl chooses to tell a writer that correspondence was selected/published

There is **no guarantee** of notification.

Private email is **not** public attribution.

It is **not** automatically a marketing-list subscription.

No private-correspondence relationship or guaranteed personal reply is created.

If `named_credit` is selected, publication uses the **separate Public Credit field** — not the email address and not the private Participant name unless that name was also supplied as Public Credit.

Anonymous publication must not expose private identity or contact information.

**Why:** Accidental disclosure of private email as attribution would violate both product trust and permission intent.

Writer **opt-in** to selection/publication notification is **not** established as a V1 product requirement. Noèl may optionally notify. See §45 regarding Product Model §17.12.

---

## 11. COPYRIGHT / LICENSE PRODUCT INTENT

**LOCKED PRODUCT INTENT**  
**COUNSEL REVIEW REQUIRED**

The writer **retains copyright**.

The intended Mail Room rights model is a **broad, durable, non-exclusive license** for appropriately authorized/selected correspondence rather than transfer of the writer's copyright ownership.

The intended rights need to support, where applicable:

- publication
- reproduction
- quotation / excerpting
- editorial presentation
- formatting
- editing for length / presentation
- arrangement
- incorporation into other works
- artistic / creative transformation
- distribution
- display / exhibition
- books
- artwork
- installations
- film
- print
- digital media
- commercial uses
- future media / forms not presently enumerated

Noèl does not want to require fresh permission for every later use that falls within rights already granted.

The intended license is:

- non-exclusive
- worldwide
- for the full duration permitted by applicable law
- capable of appropriate sublicensing for publishers, galleries, museums, printers, production companies, distributors, platforms, and other parties necessary to Noèl's projects
- capable of assignment/transfer to appropriate successors, assigns, estate, trust, company, foundation, archive, or other entity holding/continuing the work

Submission does **not** automatically create royalties, profit participation, licensing fees, accounting rights, approval rights, or additional compensation.

Noèl may separately choose to compensate or collaborate with someone.

**Do NOT fabricate final enforceable contract language in this document.**

Exact license scope, operative timing, irrevocability/durability, sublicensing, assignment, duration, territory, derivative/transformative rights, and related provisions require **counsel-reviewed language before Production**.

### Rights-timing note

**COUNSEL REVIEW REQUIRED**

Do **not** treat this amendment as deciding the precise legal moment at which the broad downstream license becomes operative.

Product architecture distinguishes:

- NOT FOR PUBLIC SHARING
- share-authorized correspondence (`anonymous` / `named_credit`)
- editorial selection
- actual public publication / use

The precise enforceable mechanism that connects those distinctions to license operation must be drafted/reconciled by counsel. Engineering must not invent that moment.

---

## 12. EDITORIAL INTEGRITY

**LOCKED PRODUCT INTENT**

Where authorized material is used, Noèl may excerpt, arrange, format, edit for length/presentation, combine, or incorporate it into broader work.

Do **not** create a product rule permitting materially fabricated words to be attributed to the writer as though the writer wrote them.

The immutable private source remains unchanged.

Editorial presentation belongs on the **separate public artifact** (see §17), not as a silent rewrite of the private original.

---

## 13. THIRD-PARTY MATERIAL

**LOCKED PRODUCT INTENT**  
**COUNSEL REVIEW REQUIRED** (representations / indemnification wording)

Writers may write about other people.

Submitters are responsible for material they include and for having the authority necessary to submit/grant applicable permissions.

Public-sharing permission **never obligates** Noèl to publish.

Noèl may redact, omit, excerpt around, or decline publication of identifying, sensitive, third-party, or otherwise unsuitable material.

**V1 is text-only.**

**FUTURE / NOT V1:** PDFs, photographs, scans, screenshots, drawings, or other file attachments. They are **not accepted** in V1.

Product intent already locked for later Terms drafting (still **COUNSEL REVIEW REQUIRED**):

- writer representations that they wrote the material or have sufficient authority to submit/grant applicable permissions
- reasonable indemnification tied to submitter material / conduct / breach / representations

---

## 14. PRIVATE REVIEW LAYER

**LOCKED ARCHITECTURE REQUIREMENT**

Private review metadata is **separate** from the immutable submission.

The private review layer may contain:

- editorial status (see §15)
- Noèl's private notes
- **current / operative sharing or presentation treatment** — used for permission guards and publication decisions; initially matches the original submitted value; may change through an administered request without overwriting the immutable submission record
- permission-change / presentation-change history
- withdrawal / removal request information
- connection to any resulting public artifact
- association to related Q records, if any (those Q records remain their own immutable objects)

**Private Notes** are backstage only.

They must **never** automatically become visitor-facing content.

**Why:** Noèl needs a place to preserve editorial memory, future connections, potential P.S. relationships, and reasons for reconsideration **without altering the writer's original submission**.

---

## 15. EDITORIAL STATUS MODEL

**LOCKED ARCHITECTURE REQUIREMENT**

Canonical editorial statuses:

| Status | Meaning |
|--------|---------|
| **NEW** | Successfully received but not yet reviewed. |
| **REVIEWED** | Noèl has reviewed the submission and it is under editorial consideration. |
| **SELECTED** | Noèl has chosen it for active potential Mail Room / publication work. **SELECTED is NOT publication.** |
| **NOT SELECTED** | Noèl has decided not to select it at this time. **NOT SELECTED is NOT permanent rejection.** |
| **PUBLISHED** | A **separate** public Mail Room artifact has actually been published from / connected to the submission. **PUBLISHED is terminal as an editorial-history status.** |

**Why NOT SELECTED is not permanent rejection:** Correspondence that is not relevant today may become creatively or editorially relevant months or years later.

**Why PUBLISHED is terminal (editorial history):** Once publication occurred, that historical fact must remain auditable even if the public artifact is later withdrawn or removed (see §20).

---

## 16. ALLOWED EDITORIAL TRANSITIONS

**LOCKED ARCHITECTURE REQUIREMENT**

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
- **NOT SELECTED → SELECTED** — Reconsideration must return through **REVIEWED** first.
- **NOT SELECTED → PUBLISHED** — Must pass through SELECTED (and permission guards).
- **Any → NEW** — Submissions do not “un-arrive.”

### Rationale

| Transition | Why it exists |
|------------|---------------|
| **SELECTED → REVIEWED** | Noèl may cease active editorial work without deciding against the submission. |
| **SELECTED → NOT SELECTED** | Noèl may begin working with a selected submission and later decide not to proceed. |
| **NOT SELECTED → REVIEWED** (only) | Supports indefinite reconsideration. |
| **No NOT SELECTED → SELECTED** | Forces explicit re-entry through the consideration checkpoint. |

Admin UI must only offer valid next states from the current state.

---

## 17. PUBLIC ARTIFACT SEPARATION

**LOCKED ARCHITECTURE REQUIREMENT**

This boundary is **strict**.

The private MR submission record is **NEVER** itself turned into the public WHAT ARRIVED webpage/object.

If Noèl selects correspondence for public presentation, create/manage a **separate** public Mail Room / WHAT ARRIVED artifact.

Conceptually:

```
Private Participant
  → immutable private MR submission
    → private editorial review
      → intentional selection
        → separate public WHAT ARRIVED artifact
          → intentional publication
```

The public artifact may contain the authorized editorial presentation:

- excerpt / full text as appropriate
- anonymization / redaction
- Public Credit where applicable
- public metadata
- tags
- FOLLOW THE THREAD relationships
- other approved public presentation

The private original remains protected and unchanged.

**IMPLEMENTATION DECISION:** exact WordPress content model for the **public** artifact (custom post type vs other public CMS object), provided it is a **separate** object from the private submission store.

---

## 18. PUBLICATION AUTHORITY

**LOCKED ARCHITECTURE REQUIREMENT**

No automatic publishing.

Sharing permission, tags, questionnaire answers, AI analysis, algorithms, editorial status, or other system behavior may **NOT** automatically publish correspondence.

Only an **intentional editorial action** by Noèl, or a future administrator explicitly authorized to make publication decisions, may create/publish the separate public artifact.

**SELECTED is not PUBLISHED.** Preserve that distinction.

The backend must **never** treat successful submission as permission to auto-publish.

---

## 19. PERMISSION GUARDS

**LOCKED ARCHITECTURE REQUIREMENT**

The system must enforce relevant permission guards rather than relying solely on Noèl remembering them during publication.

### `not_public`

No admin action that creates or publishes a WHAT ARRIVED artifact **from this submission's actual correspondence** should be available.

Hard-block at the action level — not a reminder label alone.

A visibility toggle on a private record must not publish it.

This does **not** prohibit independent inspiration as described in §9.1.

### `anonymous`

Authorized public use must **not** identify the sender.

Private email / Participant identity remain private.

Noèl may redact or generalize identifying details to preserve anonymity.

### `named_credit`

Authorized public use may credit the sender using the **Public Credit exactly as originally submitted**, unless a later administered Public Credit presentation change is honored. The historical original Public Credit remains stored unchanged.

Private email remains private and is **never** used as attribution.

---

## 20. PUBLIC ARTIFACT AVAILABILITY STATE

**LOCKED ARCHITECTURE REQUIREMENT**

Editorial history and current public availability are **different dimensions**.

A **PUBLISHED** submission remains editorially **PUBLISHED** even if its public artifact later comes down.

| State | Meaning |
|-------|---------|
| **LIVE** | Artifact is currently publicly available. |
| **WITHDRAWN** | Artifact was taken down following a handled withdrawal/removal request. |
| **REMOVED** | Artifact was taken down administratively/editorially for another reason. |

Do **NOT** roll **PUBLISHED** backward merely because the public artifact is no longer live.

Removing a public artifact or discontinuing the Mail Room does **not** automatically erase the historical fact of publication.

---

## 21. WITHDRAWAL / PERMISSION-CHANGE REQUESTS

**LOCKED PRODUCT INTENT**  
**LOCKED ARCHITECTURE REQUIREMENT** (tracking / no overwrite / no unilateral delete button)  
**COUNSEL REVIEW REQUIRED** (enforceable withdrawal, revocation, deletion, and rights-survival language)

A writer may **request** withdrawal or a sharing/presentation change.

Do **not** create an automatic unilateral delete/revoke button.

Noèl may consider and administer requests **at her discretion**, subject to applicable law and the final counsel-reviewed Terms/Privacy framework.

The system must preserve:

- original sharing choice
- requested change
- request timestamp / status
- Noèl's decision
- operative editorial treatment
- relevant history

Do **not** overwrite the historical original.

Honoring a later presentation change does **not** by itself silently surrender previously granted rights.

Removing a public artifact or discontinuing the Mail Room does **not** automatically terminate rights that remain valid.

**Privacy/data deletion** and **intellectual-property/license rights** are distinct issues and must be handled accordingly, subject to applicable law.

Track requests **separately** from editorial status and public-artifact availability.

Conceptual request states:

| State | Meaning |
|-------|---------|
| **None** | No request recorded. |
| **Pending** | Request received; not yet handled. |
| **Handled** | Request processed according to applicable policy / Noèl's decision. |

Example conceptual record (schema is an **IMPLEMENTATION DECISION**):

| Field | Value |
|-------|-------|
| Original submitted permission | `anonymous` |
| Current operative treatment | `not_public` |
| Request | administered [date] / decision recorded |

**PUBLISHED** (editorial) + **WITHDRAWN** or **REMOVED** (availability) remains a valid combination.

---

## 22. RETENTION / PROVENANCE

**LOCKED PRODUCT INTENT**  
**COUNSEL REVIEW REQUIRED** (exact retention/deletion legal obligations)

Product intent is **indefinite preservation** of the private historical submission/research archive, **subject to applicable law**.

Preserve the complete historical record necessary for provenance, including appropriate:

- original submissions
- sharing choices
- Public Credit
- private Participant information
- eligibility / attestation
- Terms / consent versions and timestamps
- questionnaire versions / responses
- permission / withdrawal history
- editorial / publication relationships
- historical Terms / questionnaire wording necessary to understand past consent

Do **not** build unnecessary surveillance.

Retain only reasonable security/operational metadata.

Do **not** create device-fingerprint hoarding.

**IMPLEMENTATION DECISION:** exact operational metadata needed for security, idempotency, and rate limiting, provided it stays minimal and is not visitor-facing profiling.

---

## 23. TERMS VERSIONING

**LOCKED ARCHITECTURE REQUIREMENT**

Every Mail Room submission records:

- exact Mail Room Submission Terms version accepted
- acceptance timestamp

Preserve **immutable historical copies** of every released Mail Room Submission Terms version.

Future Terms changes govern **future** submissions as appropriate and must **not** silently rewrite the historical agreement attached to an earlier submission.

**COUNSEL REVIEW REQUIRED:** final Terms copy; how version identifiers are labeled in visitor-facing acceptance.

---

## 24. NO GUARANTEES / NO SPECIAL RELATIONSHIP

**LOCKED PRODUCT INTENT**  
**COUNSEL REVIEW REQUIRED** (exact disclaimer language)

Submission creates **no guarantee** of:

- response
- acknowledgment
- selection
- publication
- creative use
- notification
- review timeframe
- outcome

The Mail Room is **not** a private correspondence service.

Submission does **not** create an attorney-client, doctor-patient, therapist-client, fiduciary, advisory, or similar professional/confidential relationship.

This does **not** override the actual privacy/sharing choice selected by the writer.

---

## 25. SENSITIVE INFORMATION / SAFETY / LEGAL EXCEPTIONS

**LOCKED PRODUCT INTENT**  
**COUNSEL REVIEW REQUIRED** (exact legal/safety language)

Visitor-facing guidance should discourage unnecessary high-risk information such as:

- passwords
- Social Security numbers
- financial account information
- authentication credentials
- other unnecessary highly sensitive identifiers

The backend must **not** intentionally solicit those fields.

Privacy choices do **not** require concealment where disclosure is reasonably necessary for applicable law, valid legal process, security/abuse investigation, or credible safety threats.

This exception does **not** create ordinary publication/creative-use permission.

---

## 26. CRISIS / SAFETY ARCHITECTURE

**LOCKED PRODUCT INTENT**

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

**Why:** Surveillance-style backend features would change the product's character and create false safety expectations.

---

## 27. DATA MINIMIZATION

**LOCKED PRODUCT INTENT**

Do not collect additional personal information merely because a backend exists.

V1 Participant / Mail Room / questionnaire intake is limited to the fields required by §6, letter intake, sharing choice, Public Credit where applicable, Terms acceptance, and the applicable questionnaire definition.

Do **not** add unless separately approved:

- home address
- phone number
- date of birth
- government ID
- emergency contact
- accounts / passwords
- social handles
- gender
- location
- file attachments

---

## 28. QUESTIONNAIRE FRAMEWORK

**LOCKED ARCHITECTURE REQUIREMENT**

Do **NOT** hard-code NoelClark.com around exactly five Mail Room questions.

Create a **reusable private questionnaire architecture**.

A questionnaire definition may have its own:

- questionnaire identity
- title
- introduction / context
- purpose
- questions
- response formats
- version
- lifecycle / status

Different questionnaires may contain different numbers and kinds of questions.

The current five Mail Room questions are **one V1 questionnaire definition**.

Every questionnaire response receives its own **Q record / reference**.

Each submitted Q response is an **immutable historical snapshot**.

A later response creates **another** Q record. It does **not** overwrite the earlier response.

Each response records the exact questionnaire version answered.

Preserve historical questionnaire wording / context / version so old responses remain interpretable.

A questionnaire that has received responses may be **retired** but must not be casually/permanently deleted through normal administration.

A never-used draft with **no** responses may be permanently deleted.

**IMPLEMENTATION DECISION:** exact questionnaire-definition storage and admin authoring UI.

**IMPLEMENTATION DECISION:** whether backstage interpretive mapping is stored computed vs persisted. Visitor-facing surfaces must still never receive that mapping.

The existing backstage mapping (`js/mail-room-questions-mapping.js` — backstage only, not loaded visitor-facing) remains private/backstage for the current V1 Mail Room questionnaire definition.

---

## 29. GLOBAL QUESTIONNAIRE RULES

**LOCKED PRODUCT INTENT**  
**LOCKED ARCHITECTURE REQUIREMENT**

Unless a future questionnaire explicitly establishes different approved terms:

- participant must self-attest 18+
- questions are optional
- private participant name / email are collected
- responses are private / backstage
- submitted responses are immutable historical records
- exact questionnaire version is preserved
- answers may support private research, pattern recognition, analysis, and Noèl's original creative practice
- the standard questionnaire consent does **NOT** itself authorize public quotation, reproduction, or identifiable attribution of the participant's actual response
- **separate permission** would be required for public use of an identifiable actual questionnaire response
- questionnaire participation does **not** grant or modify rights to Mail Room correspondence
- questionnaire participation does **not** create a public participant profile

Do **not** expose NERV, NERVprint, STACK, STRIKE, SCAN, SPONGE, scoring, profiles, categorization, or backstage interpretive frameworks to questionnaire participants unless separately authorized in some future project.

---

## 30. POST-SUBMISSION QUESTIONNAIRE

**LOCKED ARCHITECTURE REQUIREMENT**

After a Mail Room letter has **already** been successfully accepted:

**“Your letter has been sent. Want to go one step further?”**

**YES**

- opens the optional questionnaire experience
- may securely associate the resulting Q response with the already-created MR submission / Participant context

**NO THANKS**

- completes the Mail Room experience
- stores **no** meaningless “declined questionnaire” record merely for declining

The letter has already succeeded before this choice appears.

Questionnaire failure can **never**:

- undo the letter
- alter its sharing permission
- invalidate its Terms acceptance
- change its MR reference
- duplicate it
- roll it back

Secure association must **not** use the public MR reference ID as a password or identity-verification secret (see §32).

**IMPLEMENTATION DECISION:** exact capability-token / session mechanism that binds a post-success questionnaire write to the just-accepted MR submission without making the MR ID a secret.

---

## 31. OPTIONAL ANSWER BEHAVIOR

**LOCKED ARCHITECTURE REQUIREMENT**

Every question is individually optional.

A participant may submit any subset of answers.

Unanswered questions remain genuinely unanswered.

Unsent answers remain **local** to the current visitor experience.

Do **not** autosave questionnaire drafts to the server.

**CLEAR ANSWERS** clears the current **unsent local** answers.

Cleared unsent answers:

- are not transmitted
- are not retained by Noèl
- are not recoverable by Noèl
- do not affect the already-submitted letter

CLEAR ANSWERS is **not** a retrospective deletion mechanism for questionnaire responses that were already deliberately submitted.

---

## 32. STANDALONE QUESTIONNAIRES

**LOCKED ARCHITECTURE REQUIREMENT**

Questionnaires may also appear elsewhere on NoelClark.com independently of a Mail Room submission.

Standalone questionnaire participation uses the same private Participant infrastructure.

It collects the approved core private identity information and appropriate **questionnaire** consent.

It does **NOT** require acceptance of Mail Room Submission Terms merely because the infrastructure is shared.

It does **not** automatically associate a response with a prior MR submission unless an appropriate verified/secure relationship exists.

The public MR reference ID must **never** function as a password or identity-verification secret.

**FUTURE / NOT V1:** verified identity linking that could associate a standalone Q response with an earlier MR submission.

---

## 33. PRIVATE MAIL ROOM NOTIFICATION

**LOCKED PRODUCT INTENT**  
**LOCKED ARCHITECTURE REQUIREMENT** (bell vs source of record; failure must not un-accept)  
**IMPLEMENTATION DECISION:** mail transport / provider / template

A successfully accepted MR letter **should** trigger a minimal private notification to the configured Mail Room notification destination.

Current intended destination:

`mailroomalerts@gmail.com`

That address is **operational/private** and must **never** appear visitor-facing or be hard-coded into public frontend code.

The email is a **notification bell**, not the source of record.

The authoritative record is the private WordPress backend.

The MR alert should be minimal and must **not** contain:

- full letter
- participant name
- participant private email
- questionnaire answers
- other unnecessary private content

Appropriate alert metadata may include:

- MR reference ID
- received timestamp
- sharing choice / status
- secure admin direction / link where appropriate

**Database storage is the success authority.**

If the database write succeeds but the notification fails:

- the letter remains successfully accepted
- the MR reference remains valid
- do **not** tell the writer to resubmit
- record notification failure for administrative retry / visibility

Standalone questionnaire responses do **not** need individual email alerts.

---

## 34. IDEMPOTENCY / DUPLICATES / ABUSE

**LOCKED ARCHITECTURE REQUIREMENT**

One intentional SEND transaction must create **at most one** accepted MR submission even if there is a double-click, retry, timeout, or network replay.

Use **transaction-level idempotency**.

Do **not** use fuzzy letter-text deduplication.

A writer may intentionally submit the same/similar words again later and receive a **separate** MR record.

Repeat submissions are allowed.

Each is independently governed by its own:

- MR ID
- sharing choice
- Public Credit if applicable
- Terms acceptance / version
- provenance

The system may reject, throttle, block, or discard spam, automation, malicious flooding, abusive interference, or similar invalid traffic.

A rejected request is **not** an accepted submission and receives **no** successful MR reference.

**IMPLEMENTATION DECISION:** exact idempotency-key, rate-limit, honeypot, and abuse-control mechanisms.

---

## 35. PRIVATE ADMIN ACCESS

**LOCKED ARCHITECTURE REQUIREMENT**

Participant records, MR submissions, Q responses, private notes, private contact information, and related research/editorial records require **restricted administrative access**.

Ordinary WordPress/content-editor access must **not** automatically grant access to this private database.

Use a dedicated capability / access boundary appropriate to the final WordPress implementation.

Private records must **not** be exposed through:

- public WordPress search
- normal public pages
- public REST responses / endpoints
- visitor enumeration
- public IDs / profiles

Exact WordPress admin UI remains an **IMPLEMENTATION DECISION**.

Exact capability name / role mapping remains an **IMPLEMENTATION DECISION**, provided the dedicated boundary exists.

---

## 36. PRIVATE DASHBOARD EXPERIENCE

**LOCKED PRODUCT INTENT**  
**IMPLEMENTATION DECISION:** exact WordPress screen layout, block editor usage, and plugin choices

The private WordPress Mail Room should prioritize the **actual letter** rather than resemble a generic database screen.

A submission view should make it easy to understand:

- actual letter
- MR reference ID
- received date / time
- associated Participant (structurally distinct from Public Credit)
- editorial status
- sharing permission (admin shorthand + stored value; visitor-facing label available on demand)
- Public Credit where applicable
- private email
- 18+ attestation
- Terms version / acceptance timestamp
- related Q records, if any (as associated objects — not fields inside the immutable letter)
- private notes
- withdrawal / permission-change information
- notification-failure state, if any
- connected public artifact and its availability state, if one exists

Public Credit and private contact information should be **visually and structurally distinct**.

The private inbox should eventually support practical retrieval/filtering such as:

- New / Reviewed / Selected / Not Selected / Published
- permission tier
- MR reference ID

---

## 37. BACKUP / RECOVERY

**LOCKED ARCHITECTURE REQUIREMENT**

The private Participant, Mail Room, questionnaire, consent/provenance, and related records stored in WordPress must participate in the site's **normal secure WP Engine backup/recovery strategy**.

Backups remain private and access-restricted.

Do **NOT** create a separate ad hoc backup ecosystem or scatter manual database exports around.

**IMPLEMENTATION DECISION:** exact WP Engine backup configuration, provided these private records are included and remain access-restricted.

---

## 38. WORDPRESS RELATIONSHIP

**LOCKED ARCHITECTURE REQUIREMENT**

The approved frontend remains the **visual and interaction authority**.

WordPress implementation should provide the secure backend/admin layer underneath the approved Mail Room experience.

Do **not** rebuild the Mail Room with generic WordPress/plugin UI on the visitor-facing side.

Submission storage, Participant storage, questionnaire storage, admin, email notification, and publication workflow are **additive backend capabilities** — not a redesign mandate.

The real Mail Room backend will be built and tested on **WordPress Development** first.

Do **not** touch Production as part of implementing this authority.

---

## 39. LEGAL DOCUMENT STRUCTURE

**LOCKED PRODUCT INTENT**  
**COUNSEL REVIEW REQUIRED** (all final copy)

Product architecture assumes three distinct document jobs:

1. **General NoelClark.com Terms** — site-wide use of NoelClark.com
2. **Privacy Policy** — personal-data handling
3. **Mail Room Submission Terms** — writer-facing terms for a Mail Room letter submission

For conflicts specifically concerning a Mail Room submission, the Mail Room Submission Terms are intended to control as appropriate.

Standalone questionnaire participation uses appropriate **questionnaire** consent and does **not** require Mail Room Submission Terms merely because Participant infrastructure is shared.

Do **NOT** fabricate final legal copy in this authority.

---

## 40. CONTACT SEPARATION

**LOCKED PRODUCT INTENT**  
**LOCKED ARCHITECTURE REQUIREMENT**

Contact remains a **different system** and a **different door**.

Do **NOT** give Contact:

- MR IDs
- Mail Room permissions
- questionnaire workflow
- WHAT ARRIVED publication workflow
- Mail Room editorial statuses
- Participant / publication behavior merely because some engineering infrastructure may eventually be shared

Contact is practical communication. The Mail Room is correspondence.

---

## 41. DEVELOPMENT / PRODUCTION BOUNDARY

**LOCKED ARCHITECTURE REQUIREMENT**

The real Mail Room backend will be built and tested on WordPress Development first.

Do **not** create a fake/simulated success architecture.

Do **not** touch Production as part of this authority amendment.

This authority amendment itself authorizes **no** implementation changes.

Production SEND must not be represented as accepting letters until the complete system passes review and a later Production authorization exists.

---

## 42. IMPLEMENTATION DECISIONS

**IMPLEMENTATION DECISION** — not locked by this document except as constrained above.

Engineering must implement these without contradicting locked sections:

- exact WordPress private data model (custom/private tables vs other private store), provided private records are not publicly queryable
- exact public WHAT ARRIVED CMS object type
- exact endpoint technology (REST vs admin-post/AJAX or equivalent)
- exact MR / Participant / Q reference-ID generation format
- exact idempotency-key, nonce/CSRF, honeypot, and rate-limit mechanisms
- email delivery provider and template implementation
- exact dedicated admin capability / role mapping
- exact private-dashboard visual implementation
- exact publication-CMS implementation for WHAT ARRIVED artifacts
- linking model between private submission and public artifact (foreign-key shape, WordPress post ID usage)
- whether backstage questionnaire mapping is stored computed vs persisted
- exact security architecture details (encryption at rest, access logging, etc.) beyond the locked exposure boundaries
- final Production hosting configuration
- exact visitor-facing field chrome for collecting Participant name on Mail Room Private Contact, provided name and email are collected privately as required by §6

---

## 43. COUNSEL REVIEW REQUIRED

**COUNSEL REVIEW REQUIRED** before Production.

This authority does **NOT** finalize enforceable language for:

- exact copyright / license grant
- operative timing of rights (see §11 rights-timing note)
- durability / irrevocability
- sublicensing / assignment
- withdrawal / revocation
- privacy / data deletion versus surviving IP rights
- retention / deletion obligations
- indemnification
- limitation of liability
- Pennsylvania governing law / venue
- severability
- third-party processors / privacy disclosures
- safety / legal-process provisions
- legal consent / checkbox copy
- distinction between withdrawal of future use, removal of published source correspondence, and independently inspired work
- other provisions requiring enforceable legal drafting

**Product intent already locked** for later counsel drafting — **not** final legal advice:

- reasonable indemnification tied to submitter material / conduct / breach / representations
- reasonable limitation of liability to the fullest extent permitted
- Pennsylvania governing law / venue generally, subject to non-waivable law
- no mandatory arbitration / class-action waiver by default
- severability
- writer representations that they wrote the material or have sufficient authority to submit / grant applicable permissions

Do not turn these into purported final legal advice.

---

## 44. DECISION-RATIONALE RULE

**LOCKED PRODUCT INTENT**

Preserve the reasoning behind important product decisions.

Do not reduce this authority to a bare state-transition table.

| Decision | Rationale (summary) |
|----------|---------------------|
| Immutable original submissions | Source of truth for permission, withdrawal, provenance, and history |
| Questions after confirmed acceptance | The letter must be able to succeed without questionnaires; questionnaires must never gate or undo it |
| Separate Q records | Questionnaire answers are supplemental historical snapshots, not silent edits to the letter |
| Separate Participant layer | Shared private identity infrastructure without public profiles or V1 accounts |
| No automatic email/name merge | Unverified user-provided identity must not invent one person from collisions |
| Sharing = editorial eligibility | Prevents treating permission as auto-publication |
| `not_public` hard-block | Visibility switches must not publish protected correspondence |
| Public Credit exact reproduction | Attribution is the writer's supplied credit, not a cleaned identity |
| Writer retains copyright; license is product intent | Avoids inventing a rights assignment; counsel must draft the grant |
| No unilateral delete button | Discretion + history; privacy deletion ≠ automatic IP termination |
| Notification is a bell | Database accept is the product truth |
| Transaction idempotency, not fuzzy dedup | Retries must not double-create; intentional repeat letters remain valid |
| Separate public artifact | What arrived ≠ what was published |
| SELECTED ≠ PUBLISHED | Selection is not a public act |
| Dedicated admin capability | Ordinary editors must not see private letters/email/answers |
| WP Engine normal backups | Avoid ad hoc export scatter |
| Contact remains a different door | Practical inquiry ≠ correspondence |

---

## 45. AUTHORITY / CONFLICT HANDLING

Once this amendment is human-approved, this document is the **canonical product authority for the Mail Room backend/product workflow** within its stated jurisdiction.

It must **NOT** supersede:

- **`PROJECT_CONSTITUTION.md`**
- **`NOELCLARK_PRODUCT_MODEL.md`** for general product meaning **outside** Mail Room backend workflow specifics

When lower-level planning documentation contradicts this approved product model, **report the contradiction** — do not silently resolve it during implementation.

This amendment does **not** automatically rewrite other authority documents.

### Remaining genuine cross-document notes

These cannot be silently resolved from the locked decisions above and are **not** edited in those other files by this task:

| Document | Note |
|----------|------|
| **`NOELCLARK_PRODUCT_MODEL.md` §17.10** | Still marks final legal Submission Terms (license, copyright, quotation, withdrawal, etc.) as unresolved pending legal drafting. This amendment records **product intent** only. Compatible if §17.10 continues to mean “counsel must draft,” not “product meaning is unknown.” |
| **`NOELCLARK_PRODUCT_MODEL.md` §17.11** | Still marks the legal distinction among future-use withdrawal, published-source removal, and independently inspired work as unresolved. This amendment locks **tracking + discretion + privacy-vs-IP are distinct** as product architecture, not the enforceable legal outcome. |
| **`NOELCLARK_PRODUCT_MODEL.md` §17.12** | Still marks writer **opt-in** to selection/use notification as unresolved. This amendment locks that Noèl **may optionally notify** using private email, with **no guarantee**, and does **not** create a V1 writer opt-in control. Those two statements are not identical. Do not invent an opt-in checkbox from this amendment. |
| **`PROJECT_HANDOFF_v2.0.md`** | Operational snapshot still describes Write to Mail Room “Steps 1–6,” production SEND not connected, and “server-accepted IT ARRIVED.” This amendment supersedes **IT ARRIVED** as the success meaning and removes optional questions from the pre-submission transaction. Handoff operational status should be updated separately when authorized. Within Mail Room backend workflow, **this document controls**. |
| **Current frontend implementation** | `js/mail-room-submit.js` still places optional questions before SEND and shows “Submission is not yet connected.” That is implementation lag, not continuing product authority. This amendment does **not** authorize frontend or backend code changes. |
| **`css/MAIL_ROOM_TREATMENT.md`** | Correctly excludes backend/product architecture from its jurisdiction. No creative-treatment conflict. |
| **Participant name** | This amendment locks name as a V1 Participant field. The current Mail Room form collects private email and Public Credit (when applicable) but not a general private name field. Collecting Participant name is a later implementation consequence of §6, not a visual redesign authorized here. |

---

## Appendix A — Status & Availability Quick Reference

| Dimension | Values | Notes |
|-----------|--------|-------|
| **Editorial status** | NEW, REVIEWED, SELECTED, NOT SELECTED, PUBLISHED | Private review workflow |
| **Public artifact availability** | LIVE, WITHDRAWN, REMOVED | Current public visibility |
| **Withdrawal / change request** | None, Pending, Handled | Separate from editorial status |
| **Sharing choice (stored)** | `not_public`, `anonymous`, `named_credit` | Original submitted value is immutable; operative treatment may change through an administered request |
| **Letter success** | Stored private MR record + real MR ID | Only after confirmed server storage |
| **Questionnaire** | Separate Q record / none | Never inside the immutable letter; NO THANKS stores no decline row |

**PUBLISHED** (editorial) + **WITHDRAWN** or **REMOVED** (availability) is a valid and expected combination.

---

*End of document — AMENDED to incorporate newly locked Mail Room product decisions. Pending human authority audit. This file change is documentation only and authorizes no implementation, staging, commit, or deployment.*
