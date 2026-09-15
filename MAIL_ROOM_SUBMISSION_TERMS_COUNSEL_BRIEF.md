# Mail Room Submission Terms — Counsel Brief

**Document type:** Attorney-facing product brief  
**Prepared for:** Legal review and drafting of Mail Room Submission Terms (and related advice)  
**Project:** NoelClark.com Version 1 — The Mail Room  
**Status:** Product-intent brief for counsel — **not** enforceable legal language

---

## 1. Purpose of This Brief

This document describes how Noèl Clark **intends** the Mail Room submission system to operate as a product.

It is provided so counsel can draft and review the appropriate legal documents — especially the **Mail Room Submission Terms** — without having to reverse-engineer the product from code or marketing copy, and without redesigning how the Mail Room works.

Throughout this brief, two labels are used deliberately:

| Label | Meaning |
|-------|---------|
| **LOCKED PRODUCT INTENT** | How Noèl intends the product to operate. Engineering and visitor experience should follow this. It is **not** final contract language. |
| **COUNSEL REVIEW / DRAFTING NEEDED** | Legal language, enforceability, legal consequences, or unresolved mechanics that counsel must advise on. Product intent may exist, but counsel must draft or decide the legal treatment. |

**Counsel is being asked to:**

- translate and review this product intent into enforceable (or appropriately protective) legal language;
- advise where product intent requires legal caution or clarification;
- identify any locked product behaviors that counsel believes must change for legal reasons — **explicitly**, so Noèl can make a separate product decision.

**Counsel is not being asked to:**

- redesign the Mail Room creative experience or submission flow;
- invent new product features;
- treat this brief as the Submission Terms themselves;
- treat product-intent bullets as already-finalized legal conclusions.

Final enforceable language remains counsel’s work.

---

## 2. What the Mail Room Is

**LOCKED PRODUCT INTENT**

NoelClark.com is organized around two primary destinations:

1. **The Mail Room** — the incoming / correspondence side of the site.
2. **Personal Seal, P.S.** — Noèl’s authored / creative side of the site.

They are related but distinct. A letter can remain only a letter. Creative work in P.S. does not require a Mail Room origin. Material may connect across rooms when a real relationship exists, but nothing is required to travel through both.

### The Mail Room in human terms

People may write letters to Noèl through NoelClark.com. Letters may contain questions, stories, confessions, observations, memories, humor, grief, curiosity, or anything else someone genuinely wants to entrust to the practice.

Writers do **not** submit to a creative “department.” They write. Noèl decides what, if anything, she notices and where that attention may lead.

Selected correspondence may later appear publicly (for example on a “WHAT ARRIVED” browse/archive surface), connect to other work, or inform creative response in P.S. **None of those outcomes is promised.**

### What the Mail Room is not

- It is **not** a private one-to-one correspondence service.
- It does **not** promise an ongoing private exchange with Noèl.
- It is **not** the same as **Contact**, which is for practical / general inquiries (“a different door”).
- It is **not** Personal Seal, P.S. Any authored response that emerges belongs in P.S. as part of the forward-facing practice, not as a private reply obligation.

### Core product law

**SUBMISSION ≠ SELECTION ≠ PUBLICATION.**

- **Submission** means only that a letter was successfully received and stored in the private Mail Room.
- **Selection** means Noèl (or a future administrator explicitly authorized to make publication decisions) has chosen to work with it editorially.
- **Publication** means intentional creation/publication of a **separate** public artifact.

Nothing automatically publishes. Public-sharing permission means **eligibility for editorial consideration**, not a command to publish. A letter may arrive and never be selected. A letter may be selected and never become public. No public outcome is a legitimate outcome.

Submission creates **no guarantee** of response, acknowledgment, selection, publication, creative use, notification, review timeframe, or any particular outcome.

---

## 3. V1 Submission Experience

**LOCKED PRODUCT INTENT**

The intended Version 1 pre-submission sequence is:

1. **18+ attestation** (“Before you write”)
2. **Write your letter**
3. **Choose sharing permission** (exactly one of three choices)
4. **Private Contact** — Name + Email
5. **Review & Send** — including affirmation of Mail Room Submission Terms

**SEND A LETTER** succeeds only after real validated server acceptance and private storage of the submission.

### Important V1 boundaries

- The V1 letter is **text-only**. File attachments (PDFs, photographs, scans, screenshots, drawings, etc.) are **not** accepted in V1.
- Optional questionnaire / NERVprint material is **not** part of sending the letter.
- A questionnaire may be offered **only after** genuine successful submission.
- The questionnaire is optional and uses **separate** consent. Declining it does not undo the letter.
- Approved success meaning after confirmed acceptance: **“Your letter has been sent.”**  
  That means received into the **private** Mail Room only — not that the letter was read, reviewed, selected, published, answered, or creatively used.

Optional post-success invitation (product copy intent):  
**“Your letter has been sent. Want to go one step further?”**  
YES → optional questionnaire. NO THANKS → experience complete (no “declined questionnaire” record merely for declining).

---

## 4. Private Participant Information

**LOCKED PRODUCT INTENT**

Each submission is associated with a private **Participant** record shared conceptually by Mail Room letters and NoelClark.com questionnaires.

For V1, core private Participant identifying information includes:

- **private Name** (internal Participant name / display name)
- **private Email**
- **server-generated Participant ID**
- **18+ eligibility metadata** where applicable

These are **administrative / private infrastructure**. They are not public profiles, public handles, or visitor-facing identity.

### Separation rules

- Private Name is **not** automatically Public Credit.
- Email is **never** public attribution.
- Email is **not** automatic marketing-list enrollment.
- Submission does **not** create an ongoing private correspondence relationship or guarantee a reply.
- Matching names or emails do **not** automatically merge Participant records (identity is unverified in V1).

### Legitimate private administrative uses of email (product intent)

Private email may be used for legitimate Mail Room administration related to the submission, including:

- handling submission issues;
- permission / withdrawal requests;
- optional notification if Noèl chooses to tell a writer that correspondence was selected or published.

There is **no guarantee** of notification.

### Current visitor-facing Private Contact copy (product UX — not counsel-approved legal language)

> Your name and email are just for my private records. They won't be shown publicly unless you chose to be credited, and submitting a letter doesn't sign you up for emails or mean you'll receive a reply.

**Label:** Current product / visitor-facing copy for context only. **Not** counsel-approved legal language and **not** a substitute for Privacy Policy or Submission Terms.

### Fields intentionally not collected in V1 for this system

Unless separately approved: date of birth, phone, mailing address, username/password, social handles, gender, location, government ID, emergency contact, file attachments, and similar unnecessary identity fields.

---

## 5. Sharing Permission Model

**LOCKED PRODUCT INTENT**

There are **exactly three** sharing choices. There is no fourth.  
(“SHARE WITH ATTRIBUTION” is **not** canonical and must not replace “SHARE WITH MY NAME.”)

Canonical stored values for engineering / legal cross-reference (do **not** rename):

| Visitor-facing label | Stored value | Private admin shorthand |
|----------------------|--------------|-------------------------|
| NOT FOR PUBLIC SHARING | `not_public` | Not Public |
| SHARE ANONYMOUSLY | `anonymous` | Anonymous |
| SHARE WITH MY NAME | `named_credit` | Named Credit |

Public-sharing permission (`anonymous` / `named_credit`) is **eligibility for editorial consideration**, not a publication command.

---

### A. NOT FOR PUBLIC SHARING (`not_public`)

**Visitor intent (product UX):** “Keep this between you and me.”

**LOCKED PRODUCT INTENT**

- The actual submitted letter / protected expression is **not authorized** for public sharing.
- Identity and identifying details are **not authorized** for public disclosure as part of publishing the correspondence.
- As currently designed, the correspondence is **not eligible** for WHAT ARRIVED publication, quotation as source material, or inclusion of the **actual letter** in a commercial Mail Room publication.
- No Public Credit is collected.
- The system must hard-block publishing the actual correspondence from a `not_public` submission (not merely warn). A private-record visibility toggle must not publish it.
- **Independent inspiration remains separate** (see Section 8): reading a private letter does not authorize reproducing protected expression contrary to the writer’s choice, and also does not require Noèl to become “uninfluenced.”

---

### B. SHARE ANONYMOUSLY (`anonymous`)

**Visitor intent (product UX):** “You may share from my letter, but don't identify me.”

**LOCKED PRODUCT INTENT**

- The submission is **not anonymous to Noèl**. Private Name and Email are still collected for private administrative records.
- “Anonymous” refers to **public identification**, not invisibility to Noèl.
- The correspondence becomes **eligible** for editorial consideration and possible public use **without identifying the writer**.
- Authorized public use must **not** identify the sender.
- Private Name and Email remain private and must not appear as public attribution.
- No Public Credit field.
- Noèl may redact or generalize identifying details as necessary to preserve anonymity. The immutable private original remains unchanged.
- Permission still does **not** guarantee selection or publication.

---

### C. SHARE WITH MY NAME (`named_credit`)

**Visitor intent (product UX):** “You may share from my letter and credit me using the name I provide.”

**LOCKED PRODUCT INTENT**

- The correspondence becomes **eligible** for editorial consideration and possible public use using a separately supplied **Public Credit**.
- A separate Public Credit field is **required** for this choice.
- Public Credit is distinct from private Name.
- Private Name and Email may **never** be substituted for Public Credit.
- Under current product intent, Public Credit is reproduced **exactly as submitted** (including spelling, capitalization, punctuation, spacing, and apparent errors).
- A later requested Public Credit change may be considered at Noèl’s discretion; the historical original Public Credit remains stored unchanged.
- Permission still does **not** guarantee selection or publication.

---

## 6. Public Credit vs Private Identity

**LOCKED PRODUCT INTENT — fundamental distinction**

| Concept | Role |
|---------|------|
| **Private Name** | Internal Participant identity for private records / administration. |
| **Private Email** | Internal administrative contact. Never public attribution. Not automatic marketing enrollment. |
| **Public Credit** | Separately supplied **public attribution**, collected **only** when the sender chooses SHARE WITH MY NAME (`named_credit`). |

These must not be conflated.

If someone chooses anonymous or not-for-public sharing, they still provide private Name and Email for records, but that does **not** authorize public naming.

If someone chooses SHARE WITH MY NAME, publication uses **Public Credit**, not private Email, and not private Name unless that same string was also supplied as Public Credit.

Anonymous publication must not expose private identity or contact information.

---

## 7. Copyright and Intended Rights Model

### LOCKED PRODUCT INTENT

- The writer **retains copyright**.
- Noèl does **not** intend a copyright **assignment** / transfer of ownership.
- The intended model is a **broad, durable, non-exclusive license** for **appropriately authorized / selected** correspondence.
- Product intent contemplates that, where applicable, rights need to support uses such as: publication; reproduction; quotation / excerpting; editorial presentation; formatting; editing for length / presentation; arrangement; incorporation into other works; artistic / creative transformation; distribution; display / exhibition; books; artwork; installations; film; print; digital media; commercial uses; and future media / forms not presently enumerated.
- Product intent includes desire to avoid requiring fresh permission for every later use that already falls within rights granted.
- Intended license traits (product intent only): non-exclusive; worldwide; for the full duration permitted by applicable law; capable of appropriate sublicensing to publishers, galleries, museums, printers, production companies, distributors, platforms, and similar parties necessary to Noèl’s projects; capable of assignment / transfer to appropriate successors, assigns, estate, trust, company, foundation, archive, or other entity holding / continuing the work.
- Submission does **not** automatically create royalties, profit participation, licensing fees, accounting rights, approval rights, or additional compensation. Noèl may separately choose to compensate or collaborate with someone.
- **Editorial integrity:** Where authorized material is used, Noèl may excerpt, arrange, format, edit for length/presentation, combine, or incorporate it into broader work. The product must **not** permit attributing materially fabricated words to the writer as though the writer wrote them.
- The **private original** remains immutable. Editorial presentation belongs on a **separate public artifact**, not as a silent rewrite of the private original.

**Important product architecture note (not a legal conclusion):**  
The product distinguishes among:

1. NOT FOR PUBLIC SHARING  
2. share-authorized correspondence (`anonymous` / `named_credit`)  
3. editorial selection  
4. actual public publication / use  

This brief does **not** decide the precise legal moment at which any broad license becomes operative across those stages.

### COUNSEL REVIEW / DRAFTING NEEDED

Please advise and draft as appropriate regarding:

- exact enforceable license / grant language;
- operative timing of rights relative to submission, sharing choice, selection, and publication;
- durability / irrevocability;
- territory and duration;
- sublicensing;
- assignment / successors;
- editing / excerpting / transformation;
- commercial uses;
- derivative / transformative rights;
- compensation / royalty language (including confirmation of no automatic royalties if that remains intended);
- representations / warranties necessary for authorship and authority to submit / grant permissions;
- how `not_public` storage and private administrative handling interact with any license model.

Do **not** treat the bullet lists above as already-finalized contract terms.

---

## 8. Independent Inspiration and Creative Response

### LOCKED PRODUCT INTENT

Sharing permissions govern **use, publication, and identifiability** of the sender’s:

- actual letter;
- protected expression;
- other submitted source material (as applicable);
- identity / identifying details / attribution.

They are **not** intended to prohibit Noèl from:

- thinking about what she reads;
- being influenced or inspired by themes, ideas, experiences, or patterns;
- independently creating original work in response to inspiration.

A **NOT FOR PUBLIC SHARING** letter therefore remains private as submitted material / protected expression, but is **not** intended to impose a requirement that Noèl become “uninfluenced” by having read it.

This distinction does **NOT** authorize reproducing protected expression contrary to the sender’s permission.

Product law (plain statement of intent): everything entrusted to NoelClark.com may inspire; permission governs public use of the writer’s actual submission and identity, not Noèl’s independent thought or creative response.

### COUNSEL REVIEW / DRAFTING NEEDED

Please draft the legally accurate distinction among:

- unauthorized public reproduction / quotation / attribution of the writer’s protected material;
- independent creative work inspired by having encountered a submission;
- withdrawal of future public use of original submitted correspondence;
- requests to remove already-published source correspondence;
- works already created or distributed under permissions that existed at the time.

Do **not** expect this brief to define copyright idea/expression doctrine. That is counsel’s domain.

---

## 9. Selection, Publication and Editorial Process

**LOCKED PRODUCT INTENT**

- Acceptance into the private Mail Room is **not** selection.
- Selection is **not** publication.
- Publication requires **intentional human action** by Noèl (or a future administrator explicitly authorized to make publication decisions).
- The public WHAT ARRIVED artifact is a **separate object** from the private submission record. The private MR submission is never itself turned into the public webpage.
- No automatic publishing based on sharing permission, tags, questionnaire answers, algorithms, editorial status, or WordPress visibility toggles on private records.
- Noèl retains editorial discretion, including to redact, omit, excerpt around, or decline publication of identifying, sensitive, third-party, or otherwise unsuitable material.
- Public-sharing permission never obligates Noèl to publish.
- No guaranteed outcome.

Writers may write about other people. Product intent is that submitters are responsible for material they include and for having authority necessary to submit / grant applicable permissions. Exact representations and indemnification wording are for counsel (see Sections 7 and 17).

---

## 10. Withdrawal / Permission Changes / Removal

### LOCKED PRODUCT INTENT

- A writer may **request** withdrawal or a sharing / presentation change.
- There is **no** automatic unilateral delete / revoke button in the product.
- Noèl may consider and administer requests **at her discretion**, subject to applicable law and the final counsel-reviewed Terms / Privacy framework.
- The historical original submission and provenance remain architecturally **immutable** (original sharing choice preserved; later events recorded separately).
- Honoring a later presentation change does **not**, by itself, silently surrender previously granted rights under product intent.
- Removing a public artifact or discontinuing the Mail Room does **not**, by itself, automatically terminate rights that remain valid under product intent.
- **Privacy / data deletion** and **intellectual-property / license rights** are intended as **distinct** questions and must be handled accordingly, subject to applicable law.

Request tracking (product architecture): None / Pending / Handled — separate from editorial status and from whether a public artifact is currently live, withdrawn, or removed.

### COUNSEL REVIEW / DRAFTING NEEDED

Please determine enforceable treatment of, at minimum:

- withdrawal before selection;
- withdrawal after selection but before publication;
- withdrawal after publication;
- future use of original correspondence;
- removal of an existing public artifact;
- previously distributed works;
- independent work already created;
- privacy deletion requests;
- rights survival after presentation changes or artifact removal.

Do not resolve these in product documentation alone.

---

## 11. Questionnaire / NERVprint

**LOCKED PRODUCT INTENT**

- Offered only **after** confirmed successful letter submission.
- Fully optional. Every question is individually optional.
- **NO THANKS** completes the experience and does **not** create a questionnaire-response record merely for declining.
- Responses are **private / backstage**.
- Answers do **not** become public merely by answering.
- Standard questionnaire consent does **not** itself authorize public quotation, reproduction, or identifiable attribution of the participant’s actual response. Separate permission would be required for that.
- Questionnaire participation uses **separate consent** and does **not** require Mail Room Submission Terms merely because Participant infrastructure is shared (especially for standalone questionnaires elsewhere on the site).
- Questionnaire participation does **not** grant or modify rights to Mail Room correspondence.
- Declining or failing a questionnaire can **never** undo the letter, alter its sharing permission, invalidate Terms acceptance, change its reference ID, duplicate it, or roll it back.
- Internal NERVprint / NERV interpretive frameworks (including STACK, STRIKE, SCAN, SPONGE, scoring, profiles, categorization, and backstage mapping) are **not** exposed to participants unless separately authorized in a future project.

---

## 12. Age and Eligibility

### LOCKED PRODUCT INTENT

- Mail Room V1 letter submission requires **18+ self-attestation**.
- The product does **not** collect date of birth for this flow.
- The product does **not** currently include identity verification for age.
- People under 18 may still browse and explore NoelClark.com, but may not submit through this Mail Room submission flow (product UX intent).
- Questionnaire participation also requires 18+ self-attestation unless a future questionnaire establishes different approved terms.

### COUNSEL REVIEW / DRAFTING NEEDED

- enforceable minor / age language;
- whether self-attestation is sufficient;
- child-privacy implications;
- false attestation consequences;
- any jurisdiction-specific requirements.

---

## 13. Data and Historical Record

**LOCKED PRODUCT INTENT**

Each successfully accepted letter receives a unique Mail Room reference ID and becomes an **immutable historical record** including, as applicable:

- original letter text;
- original sharing permission;
- Public Credit if applicable;
- private Participant relationship / contact information;
- age attestation metadata;
- exact Mail Room Submission Terms version accepted;
- Terms acceptance timestamp;
- server acceptance timestamp;
- Mail Room reference ID;
- required provenance / history metadata.

Later permission-change requests, withdrawal / removal requests, editorial status, notes, publication relationships, and questionnaire associations belong in **separate** records / layers — they must not silently overwrite what was originally entrusted.

### Terms versioning (product architecture)

- Every submission records the exact Submission Terms version accepted and the acceptance timestamp.
- Immutable historical copies of every released Mail Room Submission Terms version must be preserved.
- Future Terms changes govern future submissions as appropriate and must not silently rewrite the historical agreement attached to an earlier submission.

### Retention

Product intent is **indefinite preservation** of the private historical submission / research archive, **subject to applicable law**.

Do not build unnecessary surveillance. Retain only reasonable security / operational metadata. Do not create device-fingerprint hoarding.

### COUNSEL REVIEW / DRAFTING NEEDED

Exact retention / deletion legal obligations; interaction with privacy deletion requests; how long private contact data may be retained for administration after withdrawal or similar requests.

---

## 14. Notifications and Communications

**LOCKED PRODUCT INTENT**

- No guaranteed personal reply to the writer.
- No guaranteed writer notification if correspondence is selected or published.
- **No V1 notification opt-in checkbox** or similar opt-in control has been authorized. Do not invent one from this brief.
- Noèl **may optionally** contact a writer privately (using private email on file) if correspondence is selected or published. Notification is not guaranteed.
- Private Email is **not** a marketing subscription.
- Internal operational notification to Noèl (a private “bell” that a letter arrived) is separate from participant communication. Database storage is the success authority; if ops notification fails, the letter remains successfully accepted. Ops alerts must not contain the full letter, participant name, private email, or questionnaire answers.

### COUNSEL REVIEW / DRAFTING NEEDED

Appropriate legal / privacy wording for optional notification, non-guarantees, and marketing-boundary language.

---

## 15. Sensitive Information / Safety / Relationship Disclaimers

**LOCKED PRODUCT INTENT**

- The Mail Room is **not** continuously monitored and is **not** an emergency / crisis service.
- Difficult, dark, controversial, political, or profane correspondence is not prohibited merely by subject matter.
- Submission does **not** create an attorney-client, doctor-patient, therapist-client, fiduciary, advisory, or similar professional / confidential relationship.
- Visitor-facing guidance should discourage unnecessary high-risk information such as passwords, Social Security numbers, financial account information, authentication credentials, and similar highly sensitive identifiers. The backend must not intentionally solicit those fields.
- Privacy choices do not require concealment where disclosure is reasonably necessary for applicable law, valid legal process, security / abuse investigation, or credible safety threats. That exception does **not** create ordinary publication / creative-use permission.
- The product should not introduce clinical diagnosis, AI crisis classification, continuous surveillance, location tracking, emergency-contact collection, DOB collection, or ID verification as Mail Room features.

### COUNSEL REVIEW / DRAFTING NEEDED

Exact disclaimer language, sensitive-information / safety language, and legal-process exception wording.  

Do not invent crisis instructions or legal disclaimers in this brief beyond restating existing product intent.

---

## 16. Separate Legal Documents

**LOCKED PRODUCT INTENT**

Product architecture assumes three distinct document jobs:

1. **General NoelClark.com Terms** — site-wide use of NoelClark.com  
2. **Privacy Policy** — personal-data handling  
3. **Mail Room Submission Terms** — writer-facing terms for a Mail Room letter submission  

For conflicts specifically concerning a Mail Room submission, the Mail Room Submission Terms are intended to control as appropriate.

**Questionnaire consent** is separate and does not require Mail Room Submission Terms merely because Participant infrastructure is shared.

This brief primarily concerns document **#3**, but counsel should advise on:

- necessary overlap and cross-references among all three;
- conflict rules;
- what belongs in Privacy vs Submission Terms vs general Terms.

---

## 17. Specific Questions for Counsel

Please advise on each of the following. **Do not treat silence in this brief as a legal answer.**

1. Exact enforceable copyright / license **grant** language  
2. Precise **operative timing** of rights relative to `not_public`, share-authorized submission, selection, and publication  
3. Durability / irrevocability  
4. Territory and duration  
5. Sublicensing  
6. Assignment / successors / estate / related entities  
7. Editing, excerpting, arrangement, incorporation, and artistic / creative transformation  
8. Commercial uses and future media / formats  
9. Derivative / transformative rights as contract terms  
10. Compensation / royalty / approval-rights language (including no-automatic-royalties intent)  
11. Withdrawal / revocation / removal mechanics across pre-selection, post-selection, and post-publication stages  
12. Distinction among: future-use withdrawal; removal of published source correspondence; independently inspired work already created  
13. Privacy / data deletion versus surviving intellectual-property / license rights  
14. Retention / deletion obligations and archive duration  
15. Indemnification (product intent wants reasonable indemnification tied to submitter material / conduct / breach / representations)  
16. Limitation of liability (product intent: reasonable limitation to the fullest extent permitted)  
17. Pennsylvania governing law / venue (product intent: generally, subject to non-waivable law)  
18. Severability  
19. Arbitration / class-action approach (product intent: **no** mandatory arbitration / class-action waiver **by default**)  
20. Third-party processors / privacy disclosures  
21. Safety / legal-process exception language  
22. Sensitive-information and crisis / non-monitoring disclaimers  
23. Legal consent / checkbox wording for Mail Room Submission Terms acceptance  
24. Visitor-facing **version labeling** for accepted Terms  
25. Authorship / authority representations and warranties  
26. Notification-related legal / privacy wording (optional notify; no guarantee; no V1 opt-in)  
27. Age / minors / self-attestation sufficiency / false attestation / child-privacy implications  
28. Quotation, attribution, anonymity, and redaction as enforceable terms  
29. Forward-looking treatment of future scans / screenshots / photographs / attachments (not accepted in V1, but contemplated in broader rights intent)  
30. International writers / cross-border transfer / multi-jurisdiction issues  
31. Cross-reference / conflict rules among general Terms, Privacy Policy, and Mail Room Submission Terms  
32. Questionnaire consent separation from Mail Room Submission Terms  

---

## 18. Technical Facts Counsel Should Know

These facts matter because they constrain launch readiness:

- Every accepted submission must store the **exact** legal-document version accepted and the **acceptance timestamp**.
- Released Terms bodies are stored as immutable historical snapshots (legal-document records keyed by document type + version label).
- At acceptance, the system resolves the **exact** named version. It does **not** silently select “latest.”
- Acceptance **fails closed** if a matching released Mail Room Submission Terms snapshot does not exist for the version the visitor accepted.
- Document type used for Mail Room Submission Terms in the backend model: `mail_room_submission_terms`.
- **No Terms snapshot is currently seeded.**
- **SEND is currently intentionally disconnected** from real server acceptance in the visitor experience. Successful private storage has not been authorized as a live production path pending legal and product readiness.
- Frontend currently collects a Terms-acceptance checkbox and references “Mail Room Submission Terms,” but there is not yet an actual Terms body / version for visitors to review as a released legal document.

Counsel’s approved Terms body and version approach are therefore prerequisites to any later authorization to seed a legal snapshot and connect real SEND.

---

## 19. Current Status / What Counsel Is Being Asked to Deliver

### Current state

- Mail Room frontend submission UX exists and has passed human QA for the intended visitor flow (including Private Contact Name + Email).
- Real SEND is **not** connected.
- No Mail Room Submission Terms **body** currently exists as an approved legal document.
- No released legal snapshot is seeded in the legal-documents store.
- Site Privacy Policy and general NoelClark.com Terms also remain unresolved as final enforceable documents (footer Privacy / Terms remain placeholders).

### Requested counsel deliverables

1. Reviewed / final **Mail Room Submission Terms** body suitable for release as a versioned legal snapshot.  
2. Advice on all flagged legal mechanics in Section 17.  
3. Approved visitor-facing consent / checkbox language (or clear markup of what must change).  
4. Recommended version-label presentation if counsel has a preference.  
5. Guidance on necessary Privacy Policy / general Terms cross-references and conflict rules.  
6. Any changes counsel believes are **legally necessary**, clearly distinguished from optional product recommendations.

### Important product boundary

If counsel recommends changing a **locked product behavior** for legal reasons, that recommendation should be identified **explicitly** so Noèl can make a product decision. It should **not** be silently incorporated as though it were already approved product intent.

---

## Final note

This brief describes intended product behavior and is provided for legal review and drafting. It is not itself the Mail Room Submission Terms and is not intended to be relied upon as legal language.

---

## Internal source map (project authorities)

This brief is based on approved product authorities and the completed counsel-brief source extraction. Primary sources:

| Authority | Role |
|-----------|------|
| `MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md` | Canonical Mail Room backend / product workflow intent (submission, Participant, sharing, license intent, withdrawal architecture, questionnaires, Terms versioning, counsel-required list) |
| `NOELCLARK_PRODUCT_MODEL.md` | Overall product meaning; Mail Room definition; submission ≠ selection ≠ publication; permission / inspiration philosophy; §17.10–§17.12 counsel notes |
| `PROJECT_HANDOFF_v2.0.md` | Operational synthesis; current implementation status; counsel-required legal language still open |
| `CONTACT_BACKEND_PRODUCT_AUTHORITY.md` | Contact vs Mail Room separation |
| `css/MAIL_ROOM_TREATMENT.md` | Visitor-facing Mail Room experiential character (not legal authority) |

Convention labels used in those authorities (**LOCKED PRODUCT INTENT**, **LOCKED ARCHITECTURE REQUIREMENT**, **COUNSEL REVIEW REQUIRED**) are preserved in meaning here as **LOCKED PRODUCT INTENT** and **COUNSEL REVIEW / DRAFTING NEEDED**.
