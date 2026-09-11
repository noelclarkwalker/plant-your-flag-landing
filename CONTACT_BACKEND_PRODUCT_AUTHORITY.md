# CONTACT BACKEND PRODUCT AUTHORITY

**Status:** APPROVED — LOCKED  
**Document:** `CONTACT_BACKEND_PRODUCT_AUTHORITY.md`  
**Purpose:** Canonical product authority for Contact form backend behavior — delivery, success/failure, and product boundaries only

---

## Status & Jurisdiction

| Authority | Jurisdiction |
|-----------|--------------|
| **`PROJECT_CONSTITUTION.md`** | Project governance and decision discipline — **supersedes this document** |
| **`NOELCLARK_PRODUCT_MODEL.md`** | Overall product meaning; Contact as practical communication distinct from Mail Room correspondence |
| **`contact.html`** / **`css/contact.css`** | Approved Contact **visitor-facing** design and form structure |
| **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`** | Mail Room backend/admin product workflow — **separate system** |
| **`CONTACT_BACKEND_PRODUCT_AUTHORITY.md`** | Contact backend **product behavior** once implemented |

**This document does NOT govern:**

- Contact visual design, layout, typography, or field markup
- Mail Room submission, review, publication, or admin workflow
- Privacy Policy, Terms, or final legal language
- WordPress implementation architecture, endpoints, plugins, or email providers
- engineering hosting, security, or infrastructure details

**Relationship to production frontend:** The approved Contact page in production is the **visual and interaction authority**. Future backend work must fit underneath that experience — not redesign it.

---

## Document Conventions

Throughout this document:

- **LOCKED** — Approved product behavior. Implementation must respect it unless a higher authority is formally amended.
- **UNRESOLVED** — Deliberately open. Must not be silently treated as decided.
- **LEGAL / POLICY — NOT FINALIZED** — Direction may exist; contractual or privacy language is not settled here.

---

## 1. CONTACT PURPOSE

**LOCKED**

Contact is for **practical/general communication** with Noèl.

Contact is **distinct from The Mail Room**.

The approved Contact page fields remain:

- Name
- Email
- I'm reaching out about
- Subject
- Message

Approved inquiry categories remain:

- General Inquiry
- Press & Media
- Collaboration / Business
- Speaking & Events
- Rights & Permissions
- NoelClark.com / Website

Do **not** change visitor-facing fields or categories through this authority or through backend implementation.

**Why:** Contact handles practical inquiries. The Mail Room handles correspondence under its own separate product architecture.

---

## 2. APPROVED V1 SUBMISSION FLOW

**LOCKED**

The conceptual production flow is:

```
Visitor completes Contact form
  → visitor presses SEND MESSAGE
  → server validates the submission
  → server securely accepts the submission
  → Contact message is delivered/routed for Noèl to receive
  → visitor receives a genuine success confirmation
```

The visitor must **NOT** receive a success confirmation merely because the browser attempted to submit the request.

**Successful server acceptance** is the threshold for visitor-facing success.

**Why:** A fake success state would misrepresent whether Noèl received the inquiry — the same product integrity principle applied to Mail Room IT ARRIVED behavior.

---

## 3. SUCCESS BEHAVIOR

**LOCKED**

After genuine server acceptance:

- Show a clear but design-consistent success confirmation.
- Do **not** redirect the visitor unnecessarily.
- Do **not** create a fake success state.
- Do **not** expose implementation details.
- Do **not** expose Noèl's private email address.
- Prevent accidental duplicate submission while the accepted request is being processed.

**UNRESOLVED:** Exact final visitor-facing success copy — may be approved during implementation.

---

## 4. FAILURE BEHAVIOR

**LOCKED**

If the server does **not** successfully accept the submission:

- Do **NOT** display a success message.
- Display a useful, calm error state.
- Preserve the visitor's entered message/form content where reasonably possible so they can retry.
- Do **not** silently discard the message.
- Do **not** falsely imply that Noèl received it.

**UNRESOLVED:** Exact final visitor-facing error copy — may be approved during implementation.

---

## 5. DELIVERY / PRIVATE DESTINATION

**LOCKED**

Contact submissions are intended to reach a **private destination** controlled by Noèl.

The visitor must **never** need to know Noèl's private destination address.

Do **not** expose a private email address in:

- frontend HTML
- visitor-facing JavaScript
- public configuration
- visible form markup

**UNRESOLVED:** The exact private destination address is an implementation/configuration decision and must **not** be hard-coded into this product authority.

**Why:** Practical inquiries should arrive privately without publishing Noèl's direct contact coordinates to the public web.

---

## 6. CONTACT IS NOT THE MAIL ROOM

**LOCKED**

Do **not** give Contact the Mail Room's editorial/publication architecture.

Contact does **NOT** require:

- WHAT ARRIVED publication workflow
- editorial statuses such as SELECTED or PUBLISHED
- public artifact creation
- public sharing permissions
- Mail Room optional questions
- Mail Room NERV/backstage mapping
- writer reference IDs as a product requirement
- a Mail Room-style editorial dashboard

The existing visitor-facing distinction remains:

> Want to send something to The Mail Room instead? That's a different door.

Contact is **practical communication**.  
The Mail Room is **correspondence** with its own separate product architecture governed by **`MAIL_ROOM_BACKEND_PRODUCT_AUTHORITY.md`**.

---

## 7. DATA / SECURITY PRODUCT BOUNDARIES

**LOCKED**

The backend must validate submitted data **server-side**.

Do **not** trust browser/client validation as the authority for acceptance.

The eventual implementation must include appropriate protection against abuse/spam and unsafe submission behavior.

**UNRESOLVED:** Specific mechanism (CAPTCHA, rate limiting, honeypot, etc.) — this document does **not** choose the mechanism.

Do **not** collect additional personal data merely because the backend makes it possible.

Do **not** add unless separately approved for a real product need:

- date of birth
- physical address
- phone number
- account requirement
- unnecessary tracking data

---

## 8. STORAGE

**LOCKED (product boundary)**

This authority does **NOT** currently require a permanent Contact-message database or editorial archive.

The primary V1 product need is **reliable receipt** of practical inquiries.

**UNRESOLVED:** Whether Contact submissions are additionally retained server-side, and for how long — implementation/privacy decision to resolve before production launch.

Do **not** silently establish indefinite storage.

---

## 9. ACCESSIBILITY

**LOCKED**

Backend connection must preserve the approved accessible form structure.

Preserve:

- explicit field labels
- existing field relationships (`id` / `for` / `name` mapping)
- keyboard usability

Success, error, and submitting states must be communicated accessibly.

The existing `#contact-form-status` region (referenced by `aria-describedby="contact-form-status"`) may be reused or adapted during implementation.

---

## 10. CURRENT IMPLEMENTATION STATE

**LOCKED (as of this authority)**

| Area | State |
|------|-------|
| Visitor-facing Contact frontend | **APPROVED / IMPLEMENTED** |
| Production backend | **NOT IMPLEMENTED** |
| SEND MESSAGE | **INTENTIONALLY DISABLED** |
| Current form data | **NOT TRANSMITTED OR STORED** |

No production success state may be enabled until real backend acceptance exists.

Verified production surface: `contact.html`, `css/contact.css` — no Contact-specific JavaScript; form `action="#"`; submit button `disabled` / `aria-disabled="true"`.

---

## 11. UNRESOLVED IMPLEMENTATION DECISIONS

**UNRESOLVED — NOT LOCKED BY THIS DOCUMENT**

The following remain intentionally unresolved. Do not invent answers during implementation planning unless explicitly authorized:

- WordPress implementation architecture
- endpoint/handler design
- email/delivery provider
- exact private destination configuration
- spam/abuse controls
- CSRF/security implementation
- rate limiting
- server-side validation implementation details
- whether submissions are additionally stored server-side
- retention/deletion mechanics
- logging
- hosting configuration
- exact success copy
- exact error copy

Engineering must implement these without contradicting **LOCKED** sections above.

---

## 12. PRIVACY / TERMS

**LEGAL / POLICY — NOT FINALIZED**

Final Privacy Policy and Terms language are **NOT** established by this document.

Those should be finalized after the actual Contact and Mail Room production data mechanics are known.

---

## 13. AUTHORITY RULE

**LOCKED**

This document governs Contact backend **product behavior**.

It does **not** override the approved Contact visual design.

It does **not** authorize implementation.

Future engineering decisions must preserve this product behavior unless Noèl explicitly revises this authority.

When documentation and implementation disagree, report the gap — do not silently resolve it against this authority.

---

*End of document — APPROVED — LOCKED.*
