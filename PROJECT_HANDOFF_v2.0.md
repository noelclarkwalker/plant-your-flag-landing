# PROJECT_HANDOFF_v2.0.md

**Status:** APPROVED — LOCKED  
**Document:** `PROJECT_HANDOFF_v2.0.md`  
**Purpose:** Current-state operational handoff for NoelClark.com Version 1.0

---

This document is an **operational synthesis**. It describes what NoelClark.com is now, what has been approved, what is actually implemented, what remains unresolved, and what the next AI or developer should know before touching the project.

It **does not** replace the jurisdiction of governing authority documents. When this Handoff and a governing authority conflict, **the governing authority wins** in its jurisdiction.

`PROJECT_HANDOFF_v1.0.md` remains preserved as historical context. It is **not** copied here wholesale and **does not** govern superseded landing architecture.

---

## 1. GOVERNANCE / AUTHORITY MAP

### Highest governance

**`PROJECT_CONSTITUTION.md`** remains the highest project governance authority. It defines decision discipline, locked vs. unresolved rules, AI responsibilities, and amendment process.

### Constitution authority hierarchy

When conflicts occur, the Constitution resolves authority in this order:

1. **`PROJECT_CONSTITUTION.md`**
2. **`PROJECT_HANDOFF_v2.0.md`**
3. Approved project authority documents
4. GitHub production repository
5. Current conversation

**Important:** The Constitution `# Amendment Process` provision synchronizes Version 1.0 **landing architecture** and the **landing-to-homepage relationship** to **`NOELCLARK_PRODUCT_MODEL.md` §14 (APPROVED — LOCKED)**. Superseded Portal → Emergence → closing **PLANT YOUR FLAG** → Wander spine language in `PROJECT_HANDOFF_v1.0.md` §6, `docs/DECISIONS.md`, or other unsynchronized documents **must not override Product Model §14**.

**`PROJECT_HANDOFF_v2.0.md`** is the **APPROVED — LOCKED current-state operational synthesis** at Constitution hierarchy position #2. It provides project-wide operational orientation. It does **not** override approved governing authority documents within the specific jurisdictions those documents govern.

**`PROJECT_HANDOFF_v1.0.md`** remains preserved as historical context and audit reference but is **not** current authority in the active hierarchy. Handoff v1.0 contains historical and superseded architecture and **must not** be used to override later topic-specific constitutional synchronization — including landing, where the Constitution `# Amendment Process` explicitly assigns governing authority to **`NOELCLARK_PRODUCT_MODEL.md` §14**.

### What governs what

| Authority | Jurisdiction |
|-----------|--------------|
| **`PROJECT_CONSTITUTION.md`** | Project governance, decision discipline, locked vs. unresolved, AI/engineering responsibilities |
| **`NOELCLARK_PRODUCT_MODEL.md`** (APPROVED — LOCKED) | Product meaning, two-room model, correspondence, membership/access principles, connections, landing-to-homepage product relationship (§14), retired assumptions, explicitly unresolved product questions |
| **`WEBSITE_VISUAL_AUTHORITY.md`** (APPROVED — LOCKED) | Approved high-fidelity visual composition for Homepage, Mail Room browse/archive, and correspondence detail; locked mockup reference |
| **`DESIGN_SYSTEM.md`** (APPROVED — LOCKED) | Global visual language — typography roles, palette, visual craft, cross-site design consistency |
| **`css/SITE_INTERACTION_LANGUAGE.md`** | Universal interaction and motion philosophy within the Product Model architecture |
| **`css/MAIL_ROOM_TREATMENT.md`** (APPROVED — LOCKED) | Mail Room experiential character and room-specific creative behavior |
| **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED) | Mail Room backend/admin product workflow — real accept + MR ID, immutable private letter record, supplemental questionnaire/Q records, Participant layer, private review, publication objects, permission/withdrawal tracking, notification intent (**visitor submission backend implemented; private admin inbox/review layer and publication not yet implemented** — see §6) |
| **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED) | Contact backend **product behavior** — server-acceptance success threshold, failure behavior, private delivery boundary, Contact/Mail Room separation, data-minimization intent, backend product constraints (**Contact backend implemented** — see §7) |
| **GitHub production repository** | What is actually built and deployed. Production is the WordPress theme **`wordpress/noelclark-v1`** (hosted on WP Engine); root static HTML/CSS/JS files are the static prototype (backend-dependent controls such as Mail Room SEND and Contact SEND MESSAGE are intentionally not connected there) |
| **`PROJECT_HANDOFF_v2.0.md`** (this document) | Current-state synthesis only — operational orientation, approved-vs-implemented gaps, unresolved register, workflow reminders |

### Jurisdictional rule

Handoff v2.0 **describes** current state. It does **not** override Product Model, Visual Authority, Design System, Site Interaction Language, or room-specific treatments in their jurisdictions.

When documentation and implementation disagree, **identify the discrepancy** rather than silently resolving it.

---

## 2. CURRENT PRODUCT MODEL

NoelClark.com is a **living investigation of being human**, built through curiosity, correspondence, and creative response.

### Two primary rooms

NoelClark.com currently has **two primary room concepts**:

1. **THE MAIL ROOM** — incoming / correspondence side  
2. **PERSONAL SEAL, P.S.** — authored / created side  

**Incoming → Mail Room.**  
**Noèl made it → P.S.**

These are primary Homepage destinations. They are distinct but relational. They are **not** a mandatory production pipeline.

### What is not primary architecture

- **Connections** are meaningful cross-site connective tissue, not a room. Zero connections is valid. Relationships must be genuine; do not force them.
- **Forms / mediums** (essay, music, photography, video, etc.) are metadata labels, not primary architecture.
- **NERV** survives as an important current concept/name. Its exact public role is **unresolved**. NERV is **not** currently a third peer room. Do not force NERV into either primary room.
- **NERVprints** are currently private/backstage to Noèl, not a public classification system.
- **Experiences, Music, Journal, and Art** are **not** peer rooms. Their former container status is superseded. Underlying creative DNA and forms may survive within Personal Seal, P.S. where the Product Model allows.

### Correspondence ethic (summary)

- Anyone may write to the Mail Room.
- The Mail Room is forward-facing, not a private correspondence service.
- Noèl's creative responses belong in Personal Seal, P.S.
- Mail Room artifacts remain Mail Room artifacts even when they connect to P.S. work.
- Payment can change access and priority; it cannot dictate what attention does.

Full product law: **`NOELCLARK_PRODUCT_MODEL.md`**.

---

## 3. CURRENT LANDING ARCHITECTURE

**Governing authority:** **`NOELCLARK_PRODUCT_MODEL.md` §14 (APPROVED — LOCKED)**, as synchronized in **`PROJECT_CONSTITUTION.md` `# Amendment Process`**.

### Implemented Version 1.0 landing spine

```
Opening PLANT YOUR FLAG
→ social-media remembered-world experience
→ Manifesto, concluding "I'M PLANTING MY FLAG."
→ ENTER NOELCLARK.COM
→ NoelClark.com Homepage
```

### Explicit product decisions

- **Portal** is **not** a mandatory Version 1 landing passage.
- **Emergence** is **not** a mandatory Version 1 landing stage.
- **Wander** is **not** the mandatory immediate destination after landing.
- There is **no** second / closing **PLANT YOUR FLAG** after the entrance.
- **Mail Room** and **P.S.** are **Homepage choices**, not mandatory landing chapters.
- The entrance is intentionally **monogram-free**: after "I'M PLANTING MY FLAG." the visitor sees **ENTER NOELCLARK.COM**, a visitor-controlled accessible action that leads to the implemented Homepage (`#scene-03`). Approved after visual QA and implemented in commit `97cab27` ("Remove monogram from entrance").
- No entrance monogram is present, required, or pending. Monogram image assets may remain in the repository but are not part of the entrance. The separate black monogram used elsewhere on the site (`assets/images/black-monogram-nc.png`, Homepage hero) remains valid.
- Homepage entry/session behavior is implemented: after entering, returning Home within the same browsing session restores directly to the Homepage instead of replaying PLANT YOUR FLAG (commit `bd84cec`).
- **Authority conflict (reported, not resolved here):** **`NOELCLARK_PRODUCT_MODEL.md` §14** (APPROVED — LOCKED) still names the final colorful NC monogram, and the visitor clicking it, as the threshold to the Homepage. That locked text has not been amended to reflect the monogram-free entrance.

### Superseded landing continuation (do not restore)

```
NC monogram
→ The Mail Room
→ Personal Seal, P.S.
→ closing clickable PLANT YOUR FLAG
→ Homepage
```

---

## 4. GEOGRAPHY OF CURIOSITY

- The **concept survives** as a preserved project idea.
- **Mandatory old Portal/Emergence placement does not** survive for Version 1.0.
- **Version 1.0 placement remains unresolved.**
- Do **not** restore it as a mandatory Portal or Emergence stage.
- Do **not** assume it occurs between the entrance and the Homepage.
- Its eventual placement, form, or use must be decided separately.

**This Handoff does not solve placement.**

---

## 5. HOMEPAGE / NAV

### Approved primary navigation destinations

Per **`NOELCLARK_PRODUCT_MODEL.md` §14** and **`WEBSITE_VISUAL_AUTHORITY.md` §4**:

- **THE MAIL ROOM**
- **P.S.**
- **ABOUT**
- **JOHN CLARK**
- **CONTACT**
- **BOOK** — replaces the former SHOP item and links to Return to Nature (`/return-to-nature/`; commit `6df180b`). BOOK is not listed in Product Model §14 or Visual Authority §4 navigation.

Membership/support is visible but **secondary** — a depth/access layer, not a third equal room.

The John Clark Homepage line is **"For My Daddy, M.A.Y."** (commit `ca2c8a1`).

**Journal** and **Art** are **not** primary nav destinations. Retiring them from navigation does not retire writing or art as forms within Personal Seal, P.S.

### Approved visual reference

- **`WEBSITE_VISUAL_AUTHORITY.md`** (APPROVED — LOCKED)
- Locked mockup: **`assets/design/noelclark-website-approved-v1.png`**

**Do not** treat mockup filler copy, sample correspondence, sample names, generated slogans, or other placeholder content as approved editorial authority.

### Approved Homepage composition (summary)

- White editorial/hero field → concentrated two-door destination field (Mail Room + P.S.) → black anchoring footer/utility field
- Mail Room: concentrated purple field / envelope language
- Personal Seal, P.S.: concentrated hot-pink field / P.S. object language
- Contemporary interface; artifact age belongs to the artifact, not the interface

Exact Homepage interaction choreography, responsive behavior, and editorial copy remain **governed elsewhere or unresolved** unless separately approved/locked.

---

## 6. MAIL ROOM

**Governing treatment:** **`css/MAIL_ROOM_TREATMENT.md`** (APPROVED — LOCKED)  
**Governing visual composition:** **`WEBSITE_VISUAL_AUTHORITY.md`** §5–§6

### Handoff orientation (not a duplicate of the treatment)

- The Mail Room is **incoming correspondence** — what arrived — not a container for Noèl's full authored responses.
- Visitors reach it as a **primary destination from the Homepage**, not as a mandatory landing chapter.
- Emotional center: personal invitation, hospitality, curiosity; calm describes **interaction pressure**, not visual subduing.
- Browse/archive: contemporary digital archive of encounters; anti-feed (not anti-scroll); each artifact worth stopping for.
- Experiential rhythm: **NOTICE → WONDER → ENCOUNTER → READ** (emotional order, not mandatory four-step UI).
- **Write to Mail Room** belongs in the experience.
- Connections to P.S. appear when genuine; zero connections is valid.
- **WHAT ARRIVED** (Mail Room browse/archive surface) — visitor-facing frontend **implemented / approved:**
  - WHAT ARRIVED browse/archive
  - client-side search/filter/tag behavior
  - load-more behavior
  - individual correspondence reading/dialog experience
  - Write to the Mail Room submission flow (implemented — see below)
- **Visitor submission flow and backend — implemented; Production end-to-end pass complete:**
  - Pre-submission: Before you write (18+ attestation) → Write your letter → Sharing permission → Private contact → Review & send → **SEND A LETTER** (`wordpress/noelclark-v1/assets/js/mail-room-submit.js`)
  - Real acceptance: the server validates and stores the private submission and creates a real MR reference ID; success is shown only after confirmed storage (`wordpress/noelclark-v1/inc/mail-room/` — `rest-submit.php`, `submit.php`, `schema.php`)
  - Visitor-facing success display: **“IT ARRIVED.”** — received into the private Mail Room only; not read, selected, published, or answered
  - The optional questionnaire / NERVprint is offered **only after a genuine successful submission**, is supplemental, and is **not** part of the immutable original letter (`rest-questionnaire.php`, `association.php`)
  - Production Submission Terms version: `mail-room-submission-terms-2026-10-11-prod1`
  - Production optional questionnaire version: `mail-room-optional-questions-2026-10-11-prod1`
  - Operational alerts go to **mailroomalerts@gmail.com** and remain privacy-minimal (reference, accepted-at time, sharing choice — no letter content) (`notify.php`)
  - The static prototype (`mail-room.html`, `js/mail-room-submit.js`) intentionally keeps SEND not connected; it is not the production flow.
- **Authority conflict (reported, not resolved here):** **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED) §5 still locks “Your letter has been sent.” as the success copy, and its §45 records that wording as superseding IT ARRIVED. The approved, implemented success display is “IT ARRIVED.” The locked authority has not been amended.
- **Next required build — private Mail Room admin inbox/review layer — NOT implemented:**
  - must use the existing dedicated capability `noelclark_manage_mail_room` (`inc/mail-room/capabilities.php`)
  - required concepts: editorial status, private notes, current sharing treatment, history, linked optional questionnaire answers, and filtering by status, sharing, and reference ID
  - locked editorial statuses: **NEW, REVIEWED, SELECTED, NOT SELECTED, PUBLISHED**
  - exact WordPress admin engineering is not decided in this Handoff; do not invent it here
- **Publication / WHAT ARRIVED publishing — separate later slice, NOT implemented.** WHAT ARRIVED still renders from the seed registry `js/mail-room-data.js`; there is no publication workflow backend and no auto-publish.
- **Unresolved (counsel):** final enforceable legal/policy language (Submission Terms, Privacy, Terms) — **COUNSEL REVIEW REQUIRED**.
- Mail Room backend/admin product behavior is governed by **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED).

---

## 7. CONTACT

**Governing product authority:** **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED)

### Handoff orientation (not a duplicate of the authority)

- Contact is for **practical/general communication** with Noèl — distinct from The Mail Room.
- The approved Contact/Mail Room distinction is present in production: *Want to send something to The Mail Room instead? That's a different door.*
- **Visitor-facing frontend — implemented / approved:** Name, Email, I'm reaching out about, Subject, Message; approved inquiry categories; Mail Room distinction note. Production template: `wordpress/noelclark-v1/page-contact.php` (+ `assets/css/contact.css`, `assets/js/contact.js`). The static prototype `contact.html` intentionally keeps SEND MESSAGE disabled.
- **Backend — implemented; Production Contact pass complete** (commits `9692b4a`, `4f654fd`):
  - custom WordPress theme PHP handler (the approved architecture family) — same-page POST handled on `template_redirect` with Post/Redirect/Get (`inc/contact/handler.php`)
  - server-side validation (`inc/contact/validation.php`); request authenticity via WordPress nonce; honeypot field for spam
  - private delivery via `wp_mail` to **hi@noelclark.com**, with the visitor's address as Reply-To (`inc/contact/mail.php`); the destination is server-side only
  - WordPress outbound mail is sent through **Post SMTP / Postmark**
  - visitor-facing success ("Message sent.") is shown only after server acceptance
  - no permanent Contact-message database; submissions are not stored server-side
  - no rate limiting is implemented (the Contact authority leaves the abuse-control mechanism unresolved)

### Architecture boundaries (still in force)

- Existing approved Contact design remains the **frontend authority**.
- Genuine **server acceptance** controls visitor-facing success.
- Private destination must remain **server-side/private**.
- Generic WordPress form-plugin markup must **not** replace the approved frontend.
- Contact is **not** a miniature Mail Room: it has no editorial statuses, publication objects, questionnaire, reference IDs, or Mail Room-style dashboard.

Contact backend product behavior is governed by **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED).

---

## 8. PERSONAL SEAL, P.S.

**Governing product role:** **`NOELCLARK_PRODUCT_MODEL.md` §3.2**

Personal Seal, P.S. is the **authored/created** side of NoelClark.com. It may contain essays, stories, investigations, music, photography, video, reviews, experiments, and other forms Noèl releases — as complete pieces, not medium-specific rooms.

- Work does not require a Mail Room origin.
- Noèl's authored response/work lives here.
- No required release cadence.
- Exact internal organization/taxonomy within P.S. remains **unresolved**.
- **Implementation:** P.S. is implemented / approved in the WordPress theme — browse page `page-ps.php` and individual work templates `page-ps-*.php` (commit `7439fb8` and later P.S. refinements).

### Dedicated P.S. treatment

**No dedicated P.S. room treatment document currently exists** in the repository (verified: no `P.S._TREATMENT`, `PS_TREATMENT`, or equivalent approved locked file).

Homepage and two-door visual language for P.S. is governed by **`WEBSITE_VISUAL_AUTHORITY.md`**. Room-specific experiential behavior beyond that remains **unresolved** until a separate treatment is approved.

**Do not invent a P.S. treatment in implementation.**

---

## 9. MEMBERSHIP / ACCESS

**Governing authority:** **`NOELCLARK_PRODUCT_MODEL.md` §7–§8, §11, Product Laws**

### Approved principles (summary)

- Visitors receive a genuine experience without payment.
- Members/supporters receive materially greater **depth, continuity, archival access, and correspondence priority**.
- The public/member distinction remains **depth and completeness**, not deliberately incomplete public work.
- Public released pieces are **complete**; no mid-thought paywalls for teasing.
- Membership is a **depth/access layer**, not a third content room.
- **Sealed Drawer** is a possible access metaphor only — **not** approved as a third room or required homepage object.
- Round-up patronage supports membership; provider mechanics are **not** verified/invented here.
- Priority read order for member correspondence is **not** a guarantee of human read, reply, selection, or creative output.
- Payment cannot purchase creative influence or guaranteed output.

**Do not invent:** provider mechanics, prices, tiers, guarantees, paywall UI, active-member definitions, or grace-period rules.

---

## 10. CONNECTIONS

Connections are **meaningful cross-site connective tissue**, governed by **`NOELCLARK_PRODUCT_MODEL.md` §12**.

- Material may develop genuine relationships across Mail Room, P.S., correspondence, themes, people, time periods, and creative works.
- **Zero connections is valid.**
- Do not manufacture relationships to populate an interface.
- **Follow a Thread** is a future possibility, **not** a Version 1.0 entitlement.

V1 should preserve meaningful relationship data where implemented so richer functionality remains possible later.

---

## 11. SOCIAL ENGAGEMENT METRICS

**Production requirement** (per **`docs/ROADMAP.md`** — cited here for the social-metrics production requirement only):

### Approved metrics

- **Likes**
- **Saves**

**Comments are removed from Version 1.0** — no Comment control, count, composer, guestbook, COMMENTS page, comment storage, moderation, or comment notifications. The 15 legacy WordPress comments from 2022 are unrelated and untouched.

### Required behavior at launch

- **Global**
- **Cumulative**
- **Shared across visitors, browsers, sessions, and devices**
- **Persistent across refreshes**
- **Visible to new visitors/devices as accumulated totals**
- **Once per page visit:** each reaction may succeed at most once per page visit, and each success adds 1; the heart stays red / bookmark stays gold for the rest of that visit; further clicks on that reaction do nothing and send no request; a failed request rolls back and may be retried; a genuine reload or new visit resets eligibility; back/forward cache restoration may preserve the same in-memory visit state; there is no Unlike or Unsave
- **No visitor identity:** no cookies, localStorage, sessionStorage, visitor IDs, IP tracking, fingerprinting, or other persistent visitor identity

### Current implementation

- **Storage:** two non-autoloaded `wp_options` rows (`noelclark_v1_social_like_total`, `noelclark_v1_social_save_total`), incremented by a single atomic SQL statement per click (`wordpress/noelclark-v1/inc/social/reactions.php`).
- **REST:** `GET /wp-json/noelclark/v1/social` returns `{likes, saves}` (no-store); `POST /wp-json/noelclark/v1/social/reaction` with `{"reaction": "like"|"save"}` returns `{reaction, total}`. Cross-site Origin/Referer is rejected.
- **Frontend:** `social-interactions.js` renders server totals, refreshes them live on load (homepage HTML is edge-cached), and allows each reaction to succeed once per page visit (in-memory only; duplicate clicks are ignored while a request is in flight or after success). Without WordPress REST configuration (static prototype) counts are in-memory only.
- **Production cache exclusion:** Production WP Engine has a platform-level cache exclusion for `/wp-json/noelclark/v1/social`, required so live global totals are never served stale. Production verification on 2026-10-12/13 returned `cache-control: no-store`, `x-pass-why: custom-path`, and `cf-cache-status: BYPASS`. Removing this Production cache exclusion is a regression risk. The exclusion is Production-specific.
- **Production persistence:** totals persisting after a hard refresh on Production has passed.
- **Retired:** the former `localStorage` placeholder keys (`pyf-social-metrics`, `pyf-remembered-social`, `pyf-guestbook-entries`) are removed from visitors' browsers on load and are never a counter source.

---

## 12. APPROVED VISUAL DIRECTION

Reference — do not duplicate:

| Document | Role |
|----------|------|
| **`WEBSITE_VISUAL_AUTHORITY.md`** | Approved high-fidelity composition; locked mockup |
| **`DESIGN_SYSTEM.md`** | Global visual language and tokens |
| **`css/SITE_INTERACTION_LANGUAGE.md`** | Universal interaction and motion philosophy |

### Recorded approved decisions

- Approved visual mockup exists: **`assets/design/noelclark-website-approved-v1.png`**
- **Contemporary** interface — not vintage, museum, or period-room design
- **Bright white and black** foundation; controlled high-energy brand color as punctuation
- **Approved V1 typography (implemented in `css/variables.css`):** **Instrument Sans** body (`--body-font`); **Bebas Neue** headings (`--heading-font`)
- **Authority conflict (reported, not resolved here):** **`DESIGN_SYSTEM.md`** and **`WEBSITE_VISUAL_AUTHORITY.md`** (both APPROVED — LOCKED) still name **Balgin** as the major display/headline voice, and the Design System still marks the body typeface **UNRESOLVED**. Those locked authorities have not been amended to the approved V1 typography.
- Governing principle: **The age belongs to the artifact, not the interface.**

**Do not invent** additional visual decisions beyond these authorities.

---

## 13. APPROVED VS IMPLEMENTED

Verified against the current repository — the production WordPress theme **`wordpress/noelclark-v1`** and the static prototype (`index.html`, `mail-room.html`, `contact.html`, and associated files). "Production pass" is recorded only where the project has a Production verification record; other areas are recorded as implemented / approved without a new Production claim.

| Area | Approved authority state | Current implementation state | Gap |
|------|-------------------------|------------------------------|-----|
| **Landing spine** | PYF → social experience → Manifesto → ENTER NOELCLARK.COM → Homepage (monogram-free entrance; Product Model §14 not yet amended — see §3) | `#scene-01` hero, `#scene-02` continuous social/Manifesto/entrance in one scroll (`index.html`, `front-page.php`) | None; internal code still uses legacy "portal"/"monogram" class names for the entrance |
| **Social experience** | Borrowed-land social post with authentic Likes/Saves while platform exists (Comments removed from V1) | Implemented in `#scene-02`; `arrival.js` + `social-interactions.js`; continuous social → Manifesto dissolve present | None |
| **Manifesto** | Manifesto grows from dissolved social interface; concludes with "I'M PLANTING MY FLAG." | Manifesto lines in `#scene-02`; reveal driven by `arrival.js` dissolve | Present; `js/manifesto-tension.js` exists in repo but is **not** loaded by `index.html` |
| **Entrance → Homepage handoff** | Monogram-free entrance: **ENTER NOELCLARK.COM** → Homepage | Accessible `.portal-signature__control` button revealed by scroll/intersection (`arrival.js`); enters the Homepage `#scene-03`; same-session return restores the Homepage directly (`bd84cec`); monogram removed in `97cab27` | **Implemented** |
| **Homepage** | Two-door Homepage per Visual Authority mockup | Implemented in `#scene-03` (`front-page.php`, `index.html`, `css/home.css`), including Mail Room and P.S. doors, About / John Clark / Contact / Book destinations, and the separate black monogram (`black-monogram-nc.png`) | **Implemented** |
| **Navigation** | Mail Room, P.S., About, John Clark, Contact; BOOK replaces SHOP and links to Return to Nature | Nav: The Mail Room, P.S., About, John Clark, Contact, Book (`/return-to-nature/`), Membership as a secondary button | **Implemented** (BOOK not yet reflected in Product Model §14 / Visual Authority §4) |
| **Mail Room — visitor-facing frontend** | APPROVED — LOCKED treatment + Visual Authority WHAT ARRIVED browse/detail + Write flow | `page-mail-room.php` — WHAT ARRIVED browse/archive, search/filter/tag, load-more, correspondence reading dialog, Write flow | **Implemented / approved** |
| **Mail Room — visitor submission backend** | **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** — real accept, MR ID, confirmed-storage success, supplemental questionnaire, privacy-minimal operational alert | Implemented in `inc/mail-room/`; success display “IT ARRIVED.”; optional questionnaire/NERVprint only after genuine success; Terms `mail-room-submission-terms-2026-10-11-prod1`; questionnaire `mail-room-optional-questions-2026-10-11-prod1`; alerts to mailroomalerts@gmail.com | **Implemented; Production end-to-end pass complete** (success-copy conflict with the locked authority reported in §6) |
| **Mail Room — private admin inbox/review layer** | Editorial status (NEW, REVIEWED, SELECTED, NOT SELECTED, PUBLISHED), private notes, current sharing treatment, history, linked questionnaire answers, filtering by status / sharing / reference ID; dedicated capability | Capability `noelclark_manage_mail_room` exists (`inc/mail-room/capabilities.php`); no admin UI | **Not implemented — next required build** |
| **Mail Room — publication / WHAT ARRIVED publishing** | Separate public artifact; no auto-publish | WHAT ARRIVED renders from seed registry `js/mail-room-data.js`; no publication backend | **Not implemented — separate later slice** |
| **Contact — visitor-facing frontend** | **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** + approved Contact page | `page-contact.php`, `assets/css/contact.css`, `assets/js/contact.js` | **Implemented / approved** |
| **Contact — backend delivery** | **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** — server validation, secure acceptance, private destination, genuine success/error UI; custom WordPress theme PHP handler | `inc/contact/` — same-page POST + PRG, nonce, honeypot, server validation, `wp_mail` to hi@noelclark.com via Post SMTP / Postmark; no storage | **Implemented; Production Contact pass complete** |
| **P.S.** | Primary Homepage destination; product role locked; no room treatment | `page-ps.php` browse + `page-ps-*.php` works (`7439fb8` and later) | **Implemented / approved**; dedicated room treatment document still absent |
| **About** | Primary nav destination | `page-about.php` (`3604cb7`) | **Implemented / approved** |
| **John Clark** | Primary nav destination; Homepage line "For My Daddy, M.A.Y." | `page-john-clark.php` (`58bf3ca`, `cbd3e10`, `ca2c8a1`) | **Implemented / approved** |
| **Return to Nature / BOOK** | BOOK nav destination | `page-return-to-nature.php` (`afbceab`); nav renamed in `6df180b` | **Implemented / approved** |
| **Social persistence** | Global cumulative WordPress-backed Likes/Saves; each reaction once per page visit | `wp_options` totals via `noelclark/v1/social` REST (`inc/social/reactions.php`); localStorage placeholder retired; Production cache exclusion in place | **Implemented; Production cache exclusion and hard-refresh persistence passed** |
| **Visual authority implementation** | Approved V1 typography: Instrument Sans body, Bebas Neue headings; approved palette; contemporary Homepage/Mail Room composition | `css/variables.css`: Instrument Sans body, Bebas Neue heading; Homepage and Mail Room compositions built | **Implemented**; Design System / Visual Authority still name Balgin (conflict reported in §12) |

**Never infer** that approved documentation alone proves implementation. Where implementation has been separately verified against the repository, this handoff records that verification explicitly in §13. Unverified areas remain gaps until verified.

---

## 14. CURRENT PRODUCTION FILES

Verified inventory of relevant production files (not claimed complete for the whole repository).

### WordPress theme (production) — `wordpress/noelclark-v1/`

| File | Role |
|------|------|
| `functions.php`, `inc/enqueue.php` | Theme setup, nav helpers, per-page asset enqueueing |
| `header.php`, `footer.php`, `index.php`, `style.css` | Theme shell |
| `front-page.php` | Landing (scene-01, scene-02 with monogram-free entrance) + implemented Homepage (scene-03) |
| `page-mail-room.php` | Mail Room — WHAT ARRIVED + Write flow |
| `page-contact.php` | Contact page |
| `page-ps.php`, `page-ps-*.php` | P.S. browse + individual works |
| `page-about.php`, `page-john-clark.php`, `page-return-to-nature.php` | About, John Clark, Return to Nature (BOOK) |
| `inc/social/reactions.php` | Likes/Saves totals + `noelclark/v1/social` REST |
| `inc/contact/handler.php`, `validation.php`, `mail.php` | Contact POST handling, validation, delivery |
| `inc/mail-room/*.php` | Mail Room schema/install, submission + REST, questionnaire + association, IDs, idempotency, notifications, capability, seeds |
| `inc/mail-room/MAIL_ROOM_SUBMISSION_TERMS-2026-10-11-prod1.md`, `mail-room-optional-questions-2026-10-11-prod1.json` | Production Terms / questionnaire versions |
| `assets/css/*`, `assets/js/*` | Theme copies of site CSS/JS (shared files are kept byte-identical to the static copies where behavior is shared) |

### Static prototype — entry / landing

| File | Role |
|------|------|
| `index.html` | Single-page landing: scene-01 hero, scene-02 continuous experience, scene-03 Homepage |
| `js/arrival.js` | Social dissolve, Manifesto reveal, ENTER NOELCLARK.COM reveal (legacy `monogram-actionable` / portal-stillness naming), Homepage entry + same-session restore |
| `js/feature-01-state.js` | Feature 01 state vocabulary and transitions (includes legacy emergence/wander states not implemented) |
| `js/social-interactions.js` | Likes/Saves — global WordPress-backed totals via REST (in-memory only in the static prototype) |
| `js/navigation.js` | Adds `nav-visible` on load |
| `js/world.js` | World environment behavior |
| `js/manifesto-tension.js` | Present in repo; **not referenced** by `index.html` |
| `js/rooms.js` | Present in repo; **not referenced** by `index.html` |

### Styles

| File | Role |
|------|------|
| `css/variables.css` | Color tokens, Instrument Sans body / Bebas Neue heading fonts |
| `css/hero.css` | Opening PLANT YOUR FLAG |
| `css/social.css` | Social post styling |
| `css/arrival.css` | Continuous experience / bridge |
| `css/cinema.css` | Manifesto styling |
| `css/portal.css` | Entrance stillness (legacy "portal" naming) |
| `css/navigation.css` | Global quiet nav |
| `css/home.css` | Scene-03 Homepage |
| `css/world-environment.css`, `css/world.css` | Environmental layer |
| `css/base.css`, `css/layout.css`, `css/typography.css`, `css/animations.css`, `css/reset.css` | Global foundations |

### Assets (landing-relevant)

| File | Role |
|------|------|
| `assets/images/black-monogram-nc.png` | Separate black monogram (Homepage hero) — in use |
| `assets/images/nc-monogram-watercolor.png`, `assets/images/nc-monogram-final-color.png`, `assets/images/nc-monogram-white.png` | Retained monogram assets — **not** part of the monogram-free entrance |
| `assets/noel-profile-bw.jpg` | Story ring portrait |
| `assets/design/noelclark-website-approved-v1.png` | Locked visual authority mockup |

### Static prototype — Mail Room

| File | Role |
|------|------|
| `mail-room.html` | Mail Room page — WHAT ARRIVED browse/archive, Write to Mail Room, correspondence reading dialog |
| `css/mail-room.css` | Mail Room-specific styling |
| `css/site.css` | Shared site chrome (loaded by `mail-room.html`) |
| `js/mail-room.js` | WHAT ARRIVED browse, search/filter/tag, load-more, letter reading dialog |
| `js/mail-room-data.js` | Seed artifact registry for WHAT ARRIVED (also used by the theme until the publication slice exists) |
| `js/mail-room-submit.js` | Static prototype Write dialog — SEND intentionally not connected. The production flow is the theme copy `wordpress/noelclark-v1/assets/js/mail-room-submit.js` (see §6). |
| `js/mail-room-questions.js` | Optional question prompts (visitor-facing) |
| `js/browse-core.js` | Shared browse/filter utilities (Mail Room + P.S.) |

**Backstage only (not loaded visitor-facing):**

| File | Role |
|------|------|
| `js/mail-room-questions-mapping.js` | Private backstage optional-question mapping — **not** referenced by `mail-room.html` |

### Static prototype — Contact

| File | Role |
|------|------|
| `contact.html` | Static Contact page — practical inquiry form, Mail Room distinction note (**SEND MESSAGE intentionally disabled in the static prototype**; production uses `page-contact.php`) |
| `css/contact.css` | Contact-specific styling |

No Contact-specific JavaScript is loaded by `contact.html`.

### Prototypes (not production)

- `ARRIVAL-PROTOTYPE/`
- `PERIOD-PROTOTYPE/`

### Stale engineering vocabulary in code

Production JavaScript and CSS still use **Portal** and **monogram** terminology for the monogram-free entrance (`portal-signature`, `portal-stillness`, `monogram-actionable`, `requestPortalEntry`). This reflects historical Feature 01 naming. It does **not** mean Portal/Emergence/Wander mandatory landing stages are implemented or approved.

---

## 15. LOCKED / APPROVED DOCUMENTS

List only documents whose **current files explicitly support** locked/approved status:

| Document | Status (per file) | Jurisdiction |
|----------|-------------------|--------------|
| **`NOELCLARK_PRODUCT_MODEL.md`** | APPROVED — LOCKED | Product architecture, rooms, access, correspondence, connections, landing product relationship |
| **`WEBSITE_VISUAL_AUTHORITY.md`** | APPROVED — LOCKED | Homepage, Mail Room browse/archive, correspondence detail visual composition |
| **`DESIGN_SYSTEM.md`** | APPROVED — LOCKED | Global visual language, typography roles, palette |
| **`css/MAIL_ROOM_TREATMENT.md`** | APPROVED — LOCKED | Mail Room experiential treatment |
| **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** | APPROVED — LOCKED | Mail Room backend/admin product workflow (real accept + MR ID, immutable letter record, supplemental questionnaire, Participant layer, private review, publication objects, permission/withdrawal tracking, notification intent) |
| **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** | APPROVED — LOCKED | Contact backend product behavior (server acceptance, failure behavior, private delivery, Contact/Mail Room separation, data minimization, backend constraints) |

### Governing but not marked APPROVED — LOCKED in file

| Document | Notes |
|----------|-------|
| **`PROJECT_CONSTITUTION.md`** | Highest governance authority |
| **`css/SITE_INTERACTION_LANGUAGE.md`** | Governing creative authority for universal interaction language; no explicit APPROVED — LOCKED label in file |

### Historical / superseded for mandatory V1 landing (preserved, not governing current spine)

| Document | Notes |
|----------|-------|
| **`PROJECT_HANDOFF_v1.0.md`** | Historical operational handoff; superseded Portal/Emergence/Wander spine in §6 |
| **`PORTAL_TREATMENT.md`** | Portal/Emergence creative authority for superseded architecture |
| **`EMERGENCE_TREATMENT.md`** | Emergence / Geography creative material |
| **`MANIFESTO_TREATMENT.md`** | Historical Manifesto creative reference; no APPROVED — LOCKED status in file; **cannot override Product Model §14 on landing sequence** |
| **`docs/     FEATURE_01_TREATMENT.md`** | Feature 01 creative reference; spine portions superseded |

---

## 16. UNRESOLVED REGISTER

Matters that remain **genuinely unresolved** per current authorities. **Do not solve here.**

| Matter | Source |
|--------|--------|
| **Geography of Curiosity Version 1.0 placement** | Product Model §14, §17.14 |
| **NERV exact public/product role** | Product Model §4, §17.1 |
| **Typography authority synchronization** — approved V1 typography (Instrument Sans body, Bebas Neue headings) is implemented, but Design System and Visual Authority still name Balgin and an unresolved body typeface. Locked-authority amendment requires explicit approval. | Design System Typography; Visual Authority §3 |
| **Landing authority synchronization** — the monogram-free entrance is approved and implemented, but Product Model §14 and Visual Authority still describe the final colorful NC monogram as the clickable threshold. Locked-authority amendment requires explicit approval. | Product Model §14; Visual Authority (landing relationship); `css/MAIL_ROOM_TREATMENT.md` (monogram threshold reference) |
| **Mail Room success-copy synchronization** — implemented “IT ARRIVED.” vs locked “Your letter has been sent.” Locked-authority amendment requires explicit approval. | **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** §5, §45 |
| **Mail Room private admin inbox/review layer — exact WordPress admin engineering** (not yet built; product concepts and statuses are locked — see §6) | **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** |
| **Mail Room publication / WHAT ARRIVED publishing** — separate later slice; not implemented | **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** |
| **Contact abuse-control mechanism beyond nonce + honeypot** (e.g. rate limiting — none implemented) | **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** §7 |
| **P.S. room treatment / experiential status** | No dedicated treatment file exists |
| **Exact Homepage visual composition details** (interaction choreography, responsive behavior, editorial copy; **exact navigation order**) | Product Model §14, §17.5; Visual Authority placeholder boundary |
| **Writer notification opt-in** — no V1 opt-in control has been authorized. Locked product behavior: Noèl may optionally notify via private email if correspondence is selected/published; notification is not guaranteed. | Product Model §17.12; **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** |
| **Sealed Drawer final presentation** | Product Model §17.3 |
| **Exact member/paywall interface** | Product Model §17.4 |
| **Round-up provider integration** | Product Model §17.8 |
| **Active member/supporter definition** | Product Model §17.9 |
| **Final submission legal terms** — product intent locked (writer retains copyright; intended broad, durable, non-exclusive license for appropriately authorized/selected correspondence). Enforceable copy and rights-timing remain **COUNSEL REVIEW REQUIRED**. | Product Model §17.10–§17.12; **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** |
| **Creative treatment migration** from retired Journal/Music/Experiences/Art rooms | Product Model §17.13 |
| **Follow a Thread** | Future possibility only — Product Model §17.7 |
| **Internal P.S. organization/taxonomy** | Product Model §17.2 |

---

## 17. HISTORICAL / SUPERSEDED ARCHITECTURE

The following must **not** be resurrected as mandatory Version 1.0 architecture:

- **Portal** as mandatory V1 landing passage after Manifesto
- **Emergence** as mandatory V1 landing stage (Geography of Curiosity night→day→white field)
- **Closing PLANT YOUR FLAG** after the monogram
- **Wander** as mandatory immediate post-landing destination
- **Superseded five-room / medium-room architecture** (see Product Model §16)
- **Journal / Art / Music / Experiences** as peer rooms or primary nav destinations
- **Post-monogram mandatory Mail Room and P.S. landing chapters** before Homepage
- **Public NERVprints** as visitor-facing classification
- **Portal → Emergence → closing PYF → Wander** as the governing visitor spine

**`PROJECT_HANDOFF_v1.0.md`** preserves valuable historical context — engineering notes, Manifesto decisions, social-world architecture, prototype references — but **does not govern** these superseded decisions.

**`docs/DECISIONS.md`** and **`docs/ROADMAP.md`** are synchronized to Product Model §14. Their preserved historical/superseded landing material remains historical and must not override Product Model §14.

Historical treatments (`PORTAL_TREATMENT.md`, `EMERGENCE_TREATMENT.md`, etc.) remain in the repository as design history. They are **not** deleted. They are **not** mandatory V1 landing authority.

---

## 18. WORKFLOW / AI OPERATING RULES

Salvaged from Handoff v1 and Constitution — keep concise:

### Preserve before improving

Approved work is preserved. Never redesign unless explicitly instructed.

### Inspect before editing

Read governing authorities and production code before changing anything. Do not discard approved prototype work without inspection.

### Discussion ≠ approval

Permanent decisions require: **Discussion → Explicit approval → Documentation → Implementation** (and Git commit when implementation is involved).

### Do not silently redesign

No silent redesign, optimization, modernization, simplification, reinterpretation, contradiction resolution, or promotion of discussion into permanent authority.

### Distinguish locked from unresolved

Recent conversation never upgrades an idea to LOCKED. When uncertain: **STOP**, record under UNRESOLVED, ask if implementation depends on it.

### GitHub is source of truth for production code

Conversation is temporary. Production code lives in GitHub. If documentation and code disagree, report the gap.

### Creative authority governs engineering

Engineering implements approved creative authority. Engineering does not invent story, interaction, atmosphere, pacing, philosophy, or meaning.

### Authority preservation

Superseded documents are preserved, not deleted. Record supersessions explicitly.

---

## 19. NEXT IMPLEMENTATION WORK

Based **only** on verified authority/code gaps. **This section does not authorize coding.**

### NEXT REQUIRED BUILD — PRIVATE MAIL ROOM ADMIN INBOX / REVIEW LAYER (not implemented)

Governed at product level by **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED). The visitor submission flow and backend are complete; this private layer is the next V1 build.

Required concepts:

- editorial status — locked statuses: **NEW, REVIEWED, SELECTED, NOT SELECTED, PUBLISHED**
- private notes
- current sharing treatment
- history
- linked optional questionnaire answers
- filtering by status, sharing, and reference ID

Must use the existing dedicated capability `noelclark_manage_mail_room` (`inc/mail-room/capabilities.php`). Exact WordPress admin engineering (screens, storage for notes/history, queries) is **not** decided here — do not invent it in this Handoff.

### LATER SLICE — MAIL ROOM PUBLICATION / WHAT ARRIVED PUBLISHING (not implemented)

Separate from the admin inbox/review layer. Publication creates a separate public WHAT ARRIVED artifact; there is no auto-publish. Until this slice exists, WHAT ARRIVED renders from the seed registry `js/mail-room-data.js`. Do not mark it implemented.

### AUTHORITY SYNCHRONIZATION STILL REQUIRED

| Item | Notes |
|------|-------|
| **Product Model §14 / Visual Authority landing text** | Still describe the final colorful NC monogram as the clickable threshold; the entrance is monogram-free (see §3). Requires explicit approval to amend. |
| **Design System / Visual Authority typography** | Still name Balgin and an unresolved body typeface; approved V1 typography is Instrument Sans body + Bebas Neue headings (see §12). Requires explicit approval to amend. |
| **Mail Room backend authority success copy** | §5 / §45 lock “Your letter has been sent.”; implemented success display is “IT ARRIVED.” (see §6). Requires explicit approval to amend. |
| **Navigation authority** | BOOK (→ Return to Nature) is implemented but not listed in Product Model §14 / Visual Authority §4 navigation. |
| **Historical treatments** | `PORTAL_TREATMENT.md`, `EMERGENCE_TREATMENT.md`, etc. — preserved; landing spine portions superseded |
| **Production code vocabulary** | Legacy "portal" / "monogram" naming in JS/CSS for the monogram-free entrance (documentation/engineering hygiene, not creative redesign) |

### COMPLETE — DO NOT REOPEN WITHOUT A DOCUMENTED V1 DEFECT

- **Landing + entrance** — monogram-free ENTER NOELCLARK.COM → Homepage, with same-session Homepage restore (see §3)
- **Homepage** — implemented two-door Homepage per Visual Authority (see §13)
- **Navigation** — The Mail Room, P.S., About, John Clark, Contact, Book; Membership secondary
- **Mail Room visitor-facing frontend and visitor submission backend** — Production end-to-end pass complete (see §6)
- **Contact frontend and backend** — Production Contact pass complete (see §7)
- **P.S., About, John Clark, Return to Nature / BOOK** — implemented / approved in the WordPress theme
- **Social metrics persistence** — Likes and Saves are global, cumulative, WordPress-backed totals; each reaction may succeed once per page visit, and a reload or new visit resets eligibility (no Unlike/Unsave, no persistent visitor identity). Comments are removed from V1. Production `/wp-json/noelclark/v1/social` relies on a Production-specific WP Engine cache exclusion; Production cache exclusion and hard-refresh persistence passed. See §11.

Contact is **NOT** a miniature Mail Room. The completed Contact backend does **not** complete or validate Mail Room permission tiers, editorial statuses, WHAT ARRIVED publication objects, the private Mail Room admin inbox, permission guards, or withdrawal/removal tracking.

### APPROVED REQUIREMENTS WITH IMPLEMENTATION DETAILS UNRESOLVED

1. **Relationship/connection data preservation** — meaningful relationship data must be preserved where implemented so richer connection functionality remains possible later (Product Model §12). `Follow a Thread` remains a future possibility, not a Version 1.0 entitlement. Unresolved: schema, data model, and archive mechanics. Do not invent them.
2. **P.S. room treatment** — P.S. is implemented; room-specific P.S. experiential behavior beyond the implemented pages remains unresolved pending a dedicated approved treatment; do not invent it.

**Do not invent** creative work beyond what governing authorities approve.

---

## DOCUMENT STATUS

**APPROVED — LOCKED**

Explicit human approval has been received.

This document defers to **`PROJECT_CONSTITUTION.md`**, **`NOELCLARK_PRODUCT_MODEL.md` §14**, and other locked governing documents in their jurisdictions.
