# Feature Progress

## Feature 01 — Arrival

Progress is tracked by **phase** against the governing Version 1.0 visitor spine in `docs/ROADMAP.md`.

| Phase | Status |
|---|---|
| PLANT YOUR FLAG | ✅ Complete |
| Borrowed Land | ✅ Complete |
| Static Social Post | ✅ Complete |
| Crossing (social surrender) | 🔄 In progress — social-to-Manifesto transition present; legacy state vocabulary not yet aligned |
| Manifesto | ✅ Complete — presentation present in production |
| Entrance — ENTER NOELCLARK.COM | ✅ Complete — monogram-free entrance: after I'M PLANTING MY FLAG. the visitor sees ENTER NOELCLARK.COM, a visitor-controlled accessible action (commit `97cab27`). Supersedes the earlier final-monogram reveal/click phases (historical: commit `0f1f80f`) |
| Entrance → Homepage | ✅ Complete — leads to the implemented Homepage; returning Home within the same browsing session restores the Homepage directly (commit `bd84cec`) |
| Homepage | ✅ Complete — implemented two-door Homepage |

### Retired from mandatory Version 1.0 progression (archival / prototype only)

These are **not** current required implementation phases:

Memory Field · Journal · Wayback Memory · Typewriter · Memory Cascade · Expanding Room · separate Planting My Flag state · Portal · Emergence · closing PLANT YOUR FLAG · Wander (superseded mandatory landing continuation — historical only)

Earlier prototype work may exist for some of these. It must not gate Manifesto or control Feature 01 progression unless formally re-approved.

### Notes

- Sequential Arrival is **superseded**.
- **Borrowed Land Lifts Away** applies to social-to-Manifesto surrender (`docs/DECISIONS.md`).
- The monogram-free entrance → Homepage handoff and the Homepage itself are **complete**. The earlier monogram click → Homepage boundary/stub handoff (commit `0f1f80f`) is historical and superseded by the monogram-free entrance (commit `97cab27`). Remaining landing work is engineering hygiene only: Crossing legacy state vocabulary alignment. Legacy "portal"/"monogram" naming in production code is not a mandatory visitor stage. See `PROJECT_HANDOFF_v2.0.md` §3, §13, and §19.
- Authority-document synchronization for Version 1.0 spine: **complete** after the authority correction pass.
