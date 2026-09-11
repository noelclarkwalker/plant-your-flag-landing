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
| **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED) | Mail Room backend/admin product workflow — submission records, private review, editorial status, publication objects, permission handling, withdrawal/removal tracking, notification intent (**product authority only; backend not implemented**) |
| **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED) | Contact backend **product behavior** — server-acceptance success threshold, failure behavior, private delivery boundary, Contact/Mail Room separation, data-minimization intent, backend product constraints (**product authority only; Contact backend not implemented**) |
| **GitHub production repository** | What is actually built and deployed |
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

### Approved Version 1.0 landing spine

```
Opening PLANT YOUR FLAG
→ social-media remembered-world experience
→ Manifesto
→ final colorful NC monogram
→ visitor clicks the monogram
→ NoelClark.com Homepage
```

### Explicit product decisions

- **Portal** is **not** a mandatory Version 1 landing passage.
- **Emergence** is **not** a mandatory Version 1 landing stage.
- **Wander** is **not** the mandatory immediate destination after landing.
- There is **no** second / closing **PLANT YOUR FLAG** after the monogram.
- **Mail Room** and **P.S.** are **Homepage choices**, not mandatory landing chapters.
- The **final colorful NC monogram** is the direct threshold to the Homepage.
- The landing experience through the final monogram is **approved as creatively complete** for Version 1.0.
- Product Model §14 monogram → Homepage handoff is **implemented** in production: the final colorful NC monogram is a visitor-controlled accessible action that routes to the existing `#scene-03` Homepage **boundary/stub**. **Approved Homepage implementation remains separate future work.**

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
- Do **not** assume it occurs between the NC monogram and the Homepage.
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

Membership/support is visible but **secondary** — a depth/access layer, not a third equal room.

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
  - Write to the Mail Room submission UX Steps 1–6
- **Not implemented:**
  - actual submission backend
  - secure server-side storage
  - production SEND connection (current production SEND is **not connected**)
  - production server-accepted **IT ARRIVED** behavior (**IT ARRIVED** in production must occur only after successful server acceptance)
  - private WordPress admin/dashboard
  - email notification delivery
  - publication workflow backend
- **Unresolved:**
  - exact WordPress/backend engineering implementation
  - final legal/policy language (Submission Terms, Privacy, Terms)
- Future Mail Room backend/admin product behavior is governed by **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED). That authority does **not** mean the backend itself has been implemented.

---

## 7. CONTACT

**Governing product authority:** **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED)

### Handoff orientation (not a duplicate of the authority)

- Contact is for **practical/general communication** with Noèl — distinct from The Mail Room.
- The approved Contact/Mail Room distinction is present in production: *Want to send something to The Mail Room instead? That's a different door.*
- **Visitor-facing frontend — implemented / approved:** `contact.html`, `css/contact.css` — Name, Email, I'm reaching out about, Subject, Message; approved inquiry categories; Mail Room distinction note.
- **Not implemented:** Contact backend; production SEND connection; secure server-side acceptance; private delivery routing; production success state after server acceptance.
- **SEND MESSAGE:** intentionally **disabled** in production.
- **Current Contact data:** **not transmitted or stored.**

### Approved V1 backend architecture family (engineering direction)

Contact V1 backend architecture family:

**CUSTOM WORDPRESS THEME PHP HANDLER**

The production Contact form should eventually be handled by custom WordPress/theme-side PHP rather than being rebuilt through a generic form plugin or outsourced hosted-form frontend.

**Unresolved at implementation level (do not invent here):** same-page POST handler vs WordPress `admin-post` handler — either is compatible with this family.

### Architecture boundaries (planning level — not final engineering)

- Existing approved Contact HTML/CSS remains the **frontend authority**.
- JavaScript is **not inherently required** for Contact V1.
- Server-side validation is **required**.
- Genuine **server acceptance** controls visitor-facing success.
- No permanent Contact-message database is **currently required**.
- Private destination must remain **server-side/private**.
- Generic WordPress form-plugin markup must **not** replace the approved frontend.
- Third-party hosted form architecture is **not** the preferred V1 direction.
- Exact mail transport remains **unresolved** — hosting/mail-delivery facts are not yet established in this repository.

Future Contact backend product behavior is governed by **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED). That authority does **not** mean the Contact backend itself has been implemented.

---

## 8. PERSONAL SEAL, P.S.

**Governing product role:** **`NOELCLARK_PRODUCT_MODEL.md` §3.2**

Personal Seal, P.S. is the **authored/created** side of NoelClark.com. It may contain essays, stories, investigations, music, photography, video, reviews, experiments, and other forms Noèl releases — as complete pieces, not medium-specific rooms.

- Work does not require a Mail Room origin.
- Noèl's authored response/work lives here.
- No required release cadence.
- Exact internal organization/taxonomy within P.S. remains **unresolved**.

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
- **Comments**
- **Saves**

### Required behavior at launch

- **Global**
- **Cumulative**
- **Shared across visitors and devices**
- **Persistent across refreshes**
- **Visible to new visitors/devices as accumulated totals**

### Current implementation

**`js/social-interactions.js`** uses **`localStorage`** keys (`pyf-social-metrics`, `pyf-remembered-social`, `pyf-guestbook-entries`). This is a **DEVELOPMENT PLACEHOLDER ONLY**.

**Production intent:** shared **WordPress-backed persistence** while preserving the approved landing interaction, animations, pacing, and visitor experience.

**Do not invent** API schema, backend implementation, or WordPress integration details.

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
- **Balgin** — approved major display/headline voice
- **Bebas Neue** — approved subheading/label/emphasis voice
- **Body typeface — UNRESOLVED** (Design System explicitly marks it unresolved)
- Governing principle: **The age belongs to the artifact, not the interface.**

**Do not invent** additional visual decisions beyond these authorities.

---

## 13. APPROVED VS IMPLEMENTED

Verified against the current production repository, including **`index.html`**, **`mail-room.html`**, **`contact.html`**, and associated production files.

| Area | Approved authority state | Current implementation state | Gap |
|------|-------------------------|------------------------------|-----|
| **Landing spine (through monogram)** | PYF → social experience → Manifesto → final NC monogram (Product Model §14) | `#scene-01` hero, `#scene-02` continuous social/manifesto/monogram in one scroll | Landing creative sequence largely present through monogram; internal code still uses legacy "portal" naming for monogram stillness |
| **Social experience** | Borrowed-land social post with authentic Likes/Comments/Saves while platform exists | Implemented in `#scene-02`; `js/arrival.js` + `js/social-interactions.js`; continuous social → Manifesto dissolve present | None identified for social → Manifesto continuity |
| **Manifesto** | Manifesto grows from dissolved social interface; concludes with "I'm planting my flag." | Manifesto lines in `#scene-02`; reveal driven by `js/arrival.js` dissolve | Present; `js/manifesto-tension.js` exists in repo but is **not** loaded by `index.html` |
| **Final monogram** | Colorful NC monogram after Manifesto; creatively complete | `assets/images/nc-monogram-final-color.png` in `.portal-signature`; reveal via scroll/intersection | Monogram present and revealed |
| **Landing → Homepage handoff** | Visitor **clicks** monogram → Homepage (Product Model §14) | Final colorful NC monogram is a visitor-controlled accessible action (`index.html` button; `js/arrival.js`); scrolls to `#scene-03` boundary/stub (`body.homepage-entered`, `css/home.css`) | **Complete** — handoff to Homepage boundary/stub only; approved Homepage implementation remains separate future work |
| **Homepage** | Two-door Homepage per Visual Authority mockup | `#scene-03` is minimal stub (`css/home.css` — thread placeholder only) | **Not implemented** |
| **Navigation** | Mail Room, P.S., About, John Clark, Contact | Production nav includes Mail Room, P.S., About, John Clark, Contact (`index.html`, `#scene-03` home header) | **Implemented** |
| **Mail Room — visitor-facing frontend** | APPROVED — LOCKED treatment + Visual Authority WHAT ARRIVED browse/detail + Write to Mail Room submission UX | `mail-room.html` — WHAT ARRIVED browse/archive, search/filter/tag, load-more, correspondence reading dialog, Write to Mail Room Steps 1–6 | **Implemented / approved** |
| **Mail Room — backend submission/admin** | **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** — secure intake, storage, SEND, server-accepted IT ARRIVED, private admin, notifications, publication workflow | Not implemented; production SEND not connected | **Not implemented** |
| **Contact — visitor-facing frontend** | **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** + approved Contact page | `contact.html` — practical inquiry form, Mail Room distinction note, no Contact-specific JavaScript | **Implemented / approved** |
| **Contact — backend delivery** | **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** — server validation, secure acceptance, private destination routing, genuine success/error UI; architecture family: custom WordPress theme PHP handler | Not implemented; SEND MESSAGE intentionally disabled; form data not transmitted or stored | **Not implemented** |
| **P.S.** | Primary Homepage destination; product role locked; no room treatment | No P.S. routes, pages, or archive UI in production | **Not implemented**; treatment also absent |
| **Social persistence** | Global cumulative WordPress-backed Likes/Comments/Saves at launch | `localStorage` only in `js/social-interactions.js` | **Dev placeholder only** |
| **Visual authority implementation** | Balgin display, Bebas subheads, approved palette, contemporary Homepage/Mail Room composition | Approved Mail Room visitor-facing composition built (`mail-room.html`, `css/mail-room.css`, `css/site.css`); `css/variables.css`: Inter body, Bebas heading; no Balgin token | **Partial** — Mail Room visitor UI implemented; global Balgin token and full Homepage visual authority alignment remain incomplete |

**Never infer** that approved documentation alone proves implementation. Where implementation has been separately verified against the production repository, this handoff records that verification explicitly in §13 (including Mail Room and Contact visitor-facing frontend). Unverified areas remain gaps until verified.

---

## 14. CURRENT PRODUCTION FILES

Verified inventory of relevant production files (not claimed complete for the whole repository):

### Entry / landing

| File | Role |
|------|------|
| `index.html` | Single-page landing: scene-01 hero, scene-02 continuous experience, scene-03 home stub |
| `js/arrival.js` | Social dissolve, Manifesto reveal, monogram reveal, portal stillness |
| `js/feature-01-state.js` | Feature 01 state vocabulary and transitions (includes legacy emergence/wander states not implemented) |
| `js/social-interactions.js` | Likes/Comments/Saves + localStorage metrics |
| `js/navigation.js` | Adds `nav-visible` on load |
| `js/world.js` | World environment behavior |
| `js/manifesto-tension.js` | Present in repo; **not referenced** by `index.html` |
| `js/rooms.js` | Present in repo; **not referenced** by `index.html` |

### Styles

| File | Role |
|------|------|
| `css/variables.css` | Color tokens, Inter/Bebas fonts |
| `css/hero.css` | Opening PLANT YOUR FLAG |
| `css/social.css` | Social post styling |
| `css/arrival.css` | Continuous experience / bridge |
| `css/cinema.css` | Manifesto styling |
| `css/portal.css` | Monogram stillness (legacy "portal" naming) |
| `css/navigation.css` | Global quiet nav |
| `css/home.css` | Scene-03 stub |
| `css/world-environment.css`, `css/world.css` | Environmental layer |
| `css/base.css`, `css/layout.css`, `css/typography.css`, `css/animations.css`, `css/reset.css` | Global foundations |

### Assets (landing-relevant)

| File | Role |
|------|------|
| `assets/images/nc-monogram-watercolor.png` | Final colorful NC monogram |
| `assets/noel-profile-bw.jpg` | Story ring portrait |
| `assets/design/noelclark-website-approved-v1.png` | Locked visual authority mockup (not yet implemented as UI) |

### Mail Room (visitor-facing production)

| File | Role |
|------|------|
| `mail-room.html` | Mail Room page — WHAT ARRIVED browse/archive, Write to Mail Room, correspondence reading dialog |
| `css/mail-room.css` | Mail Room-specific styling |
| `css/site.css` | Shared site chrome (loaded by `mail-room.html`) |
| `js/mail-room.js` | WHAT ARRIVED browse, search/filter/tag, load-more, letter reading dialog |
| `js/mail-room-data.js` | Seed artifact registry for WHAT ARRIVED |
| `js/mail-room-submit.js` | Write to Mail Room submission UX Steps 1–6 (**production SEND not connected**) |
| `js/mail-room-questions.js` | Optional question prompts (visitor-facing) |
| `js/browse-core.js` | Shared browse/filter utilities (Mail Room + P.S.) |

**Backstage only (not loaded visitor-facing):**

| File | Role |
|------|------|
| `js/mail-room-questions-mapping.js` | Private backstage optional-question mapping — **not** referenced by `mail-room.html` |

### Contact (visitor-facing production)

| File | Role |
|------|------|
| `contact.html` | Contact page — practical inquiry form, Mail Room distinction note (**SEND MESSAGE intentionally disabled**) |
| `css/contact.css` | Contact-specific styling |

No Contact-specific JavaScript is loaded by `contact.html`.

### Prototypes (not production)

- `ARRIVAL-PROTOTYPE/`
- `PERIOD-PROTOTYPE/`

### Stale engineering vocabulary in code

Production JavaScript and CSS still use **Portal** terminology for monogram stillness (`portal-signature`, `portal-stillness`, `requestPortalEntry`). This reflects historical Feature 01 naming. It does **not** mean Portal/Emergence/Wander mandatory landing stages are implemented or approved.

---

## 15. LOCKED / APPROVED DOCUMENTS

List only documents whose **current files explicitly support** locked/approved status:

| Document | Status (per file) | Jurisdiction |
|----------|-------------------|--------------|
| **`NOELCLARK_PRODUCT_MODEL.md`** | APPROVED — LOCKED | Product architecture, rooms, access, correspondence, connections, landing product relationship |
| **`WEBSITE_VISUAL_AUTHORITY.md`** | APPROVED — LOCKED | Homepage, Mail Room browse/archive, correspondence detail visual composition |
| **`DESIGN_SYSTEM.md`** | APPROVED — LOCKED | Global visual language, typography roles, palette |
| **`css/MAIL_ROOM_TREATMENT.md`** | APPROVED — LOCKED | Mail Room experiential treatment |
| **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** | APPROVED — LOCKED | Mail Room backend/admin product workflow (submission records, private review, editorial status, publication objects, permission handling, withdrawal/removal tracking, notification intent) |
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
| **Body typeface** | Design System Typography; Visual Authority §3 |
| **Mail Room backend / production intake** (secure submission storage, production SEND connection, server-accepted IT ARRIVED, private admin workflow, notification delivery, publication workflow backend; exact WordPress engineering) | **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`**; Product Model §17.10–§17.12 |
| **Contact backend / production delivery** (custom WordPress theme PHP handler implementation; same-page POST vs `admin-post`; hosting; mail transport/provider; spam/abuse; rate limiting; CSRF/request authenticity; optional server-side storage; retention/deletion; exact success/error copy) | **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`**; Product Model §17.10–§17.12 (legal overlap only) |
| **P.S. room treatment / experiential status** | No dedicated treatment file exists |
| **WordPress social persistence implementation** | ROADMAP production requirement; no schema/API approved |
| **Exact Homepage visual composition details** (interaction choreography, responsive behavior, editorial copy; **exact navigation order**) | Product Model §14, §17.5; Visual Authority placeholder boundary |
| **Submission-use notifications** | Product Model §17.12 |
| **Sealed Drawer final presentation** | Product Model §17.3 |
| **Exact member/paywall interface** | Product Model §17.4 |
| **Round-up provider integration** | Product Model §17.8 |
| **Active member/supporter definition** | Product Model §17.9 |
| **Final submission legal terms** | Product Model §17.10–§17.12 |
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

### AUTHORITY SYNCHRONIZATION STILL REQUIRED

| Item | Notes |
|------|-------|
| **Historical treatments** | `PORTAL_TREATMENT.md`, `EMERGENCE_TREATMENT.md`, etc. — preserved; landing spine portions superseded |
| **Production code vocabulary** | Legacy "portal" naming in JS/CSS does not match current Product Model language (documentation/engineering hygiene, not creative redesign) |

### LANDING IMPLEMENTATION — COMPLETE (Product Model §14 handoff)

The final colorful NC monogram → Homepage boundary/stub handoff is **implemented** in production. Approved Homepage implementation remains separate future work (see IMPLEMENTATION SUPPORTED BY SUFFICIENT APPROVED AUTHORITY below).

### MAIL ROOM VISITOR-FRONTEND — IMPLEMENTED / APPROVED

Verified in production:

- **Navigation** — Mail Room, P.S., About, John Clark, Contact
- **Mail Room visitor-facing frontend** — WHAT ARRIVED browse/archive, client-side search/filter/tag, load-more, correspondence reading dialog, Write to Mail Room submission UX Steps 1–6 per Visual Authority + Mail Room Treatment
- **Production SEND is not connected.** No real submission should be represented as accepted until a future secure backend confirms server acceptance.

### FUTURE MAIL ROOM BACKEND (product authority locked; engineering not implemented)

Governed at product level by **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED). Implementation details remain unresolved. Do not invent schema, endpoints, authentication, WordPress post types, plugins, email provider, reference-ID format, or security architecture.

Future work includes:

- backend intake and secure server-side storage
- production SEND connection
- production **IT ARRIVED** only after successful server acceptance
- private WordPress admin/dashboard workflow
- email notification delivery
- publication workflow backend
- eventual WordPress integration

### CONTACT VISITOR-FRONTEND — IMPLEMENTED / APPROVED

Verified in production:

- **Contact visitor-facing frontend** — `contact.html`, `css/contact.css`; approved fields and inquiry categories; Mail Room distinction note
- **SEND MESSAGE intentionally disabled.** No Contact data is transmitted or stored until a secure backend exists.

### FUTURE CONTACT BACKEND (product authority locked; engineering not implemented)

Governed at product level by **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** (APPROVED — LOCKED).

**Approved V1 architecture family:** custom WordPress theme PHP handler (not generic form plugin; not preferred third-party hosted-form frontend).

**Unresolved at implementation level:** same-page POST vs WordPress `admin-post` handler; hosting; mail transport/provider; spam/abuse; rate limiting; CSRF/request authenticity; optional server-side storage; retention/deletion; exact success/error copy. Do not invent endpoint names, PHP functions, hooks, nonce implementation, email/SMTP/transactional providers, spam mechanisms, database schema, or security architecture here.

Future work includes:

- custom theme-side server validation and secure acceptance
- production SEND connection
- genuine server-acceptance success/error UI
- private server-side destination configuration
- eventual WordPress integration under the approved Contact template

### CONTACT AS WORDPRESS BACKEND PROOF-OF-CONCEPT (planning)

Contact is a suitable **small** WordPress backend proof-of-concept. It **can** help prove:

- approved static frontend fidelity inside a custom WordPress template
- custom theme-side server handling
- server acceptance → genuine success/error UI
- private server-side destination configuration
- basic server validation/security plumbing
- static-vs-WordPress staging comparison

Contact is **NOT** a miniature Mail Room. A successful Contact backend does **not** complete or validate Mail Room six-step intake, immutable submission records, permission tiers, editorial statuses, WHAT ARRIVED publication objects, private Mail Room admin dashboard, permission guards, withdrawal/removal tracking, optional-question backstage mapping, or Mail Room reference IDs.

### IMPLEMENTATION SUPPORTED BY SUFFICIENT APPROVED AUTHORITY

The items below are supported by locked or governing authorities already in force:

1. **Homepage** — implement approved two-door Homepage per **`WEBSITE_VISUAL_AUTHORITY.md`** and locked mockup

### APPROVED REQUIREMENTS WITH IMPLEMENTATION DETAILS UNRESOLVED

The requirements below are already established. What remains unresolved is how certain implementation details will be executed:

4. **Social metrics persistence** — Likes, Comments, and Saves remain global, cumulative, shared across visitors/devices, and persistent; production intent is WordPress-backed persistence; current `localStorage` is a development placeholder only. Unresolved: API/schema/backend integration details. Do not invent those details.
5. **Relationship/connection data preservation** — meaningful relationship data must be preserved where implemented so richer connection functionality remains possible later (Product Model §12). `Follow a Thread` remains a future possibility, not a Version 1.0 entitlement. Unresolved: schema, data model, and archive mechanics. Do not invent them.

### IMPLEMENTATION PARTIALLY CONSTRAINED BY UNRESOLVED OR MISSING AUTHORITY

6. **P.S.** — approved primary destination with established product role (Visual Authority + Product Model). Room-specific P.S. experiential behavior remains unresolved pending a dedicated approved treatment; do not invent it during implementation.
7. **Design token alignment** — implement approved Balgin major display/headline and Bebas Neue subheading/label/emphasis roles where applicable. Body typeface remains **UNRESOLVED**; do not choose or invent it.

**Do not invent** creative work beyond what governing authorities approve.

---

## DOCUMENT STATUS

**APPROVED — LOCKED**

Explicit human approval has been received.

This document defers to **`PROJECT_CONSTITUTION.md`**, **`NOELCLARK_PRODUCT_MODEL.md` §14**, and other locked governing documents in their jurisdictions.
