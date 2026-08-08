ENGINEERING_PRINCIPLES
Version 1.0
This document defines the permanent engineering principles for NoelClark.com.
These principles govern how the project is implemented regardless of programming language, framework, tooling, or future refactoring. These principles apply globally unless a higher-authority project document explicitly states otherwise.
If implementation choices conflict with these principles, the implementation should change before the principles do.

1. Purpose and Scope
Engineering exists to serve the approved experience.
This document defines enduring engineering truths.
It does not define creative direction.
It does not define implementation tasks.
It does not prescribe specific technologies.
Implementation may evolve.
These principles should remain stable.
Changes to this document require intentional review and explicit approval.
Engineering principles are not revised casually during implementation.
If implementation appears to conflict with these principles, first determine whether the implementation should change before considering any modification to the principles themselves.

2. Relationship to Project Documents
MASTER_PROJECT_BRIEF.md defines the long-term product vision.
FEATURE_01_TREATMENT.md is the creative authority for the Feature 01 visitor experience.
FEATURE_01_ARCHITECTURE_REVISION.md defines the governing architecture for Feature 01.
DECISIONS.md records permanent project decisions.
ROADMAP.md defines approved project sequence.
ENGINEERING_PRINCIPLES.md defines the permanent engineering principles that govern implementation.
Engineering Plans translate these principles into feature-specific implementation.
Engineering does not reinterpret creative authority.
Engineering translates approved intent into a reliable system.

3. Visitor-First Engineering
The visitor experiences the result, not the machinery.
Technical decisions should be judged first by what the visitor perceives.
Implementation complexity is never justification for weakening an approved experience.
The system should remain quiet when the experience requires quiet.
Interaction should feel intentional.
Reading should remain under the visitor's control.
Engineering should support attention without competing for it.
When technical behavior becomes more noticeable than the experience it serves, the implementation should be reconsidered.

4. Environmental Integrity
The environment must behave as one coherent system.
Changes in state must not accidentally expose the mechanics beneath the experience.
Layout instability, unintended movement, visual discontinuity, and competing system behavior should not become part of the visitor's perception.
Elements with continuous narrative or functional identity should preserve that identity across state changes.
Environmental behavior should remain predictable even when perception changes.
The experience should degrade by becoming simpler, not by becoming incoherent.

5. State, Navigation & Tracking Philosophy
State should exist only when the project has a meaningful reason to remember something.
The system should preserve only the minimum state necessary to support the approved experience.
Navigation state must remain understandable and recoverable. Application state, navigation, and browser history should remain consistent with the visitor's understanding of where they are within the experience.
Returning-visitor behavior must never damage or erase the complete first-time experience.
Tracking must be purposeful, minimal, and subordinate to the values of NoelClark.com.
The project should not collect information merely because it can.
No metric should become more important than the experience it measures.

6. Performance Philosophy
Performance is part of the experience.
The site should feel responsive without sacrificing intentional pacing.
Necessary richness should be delivered efficiently.
Unnecessary weight should not be justified as atmosphere.
Resources should be proportional to their value to the visitor.
Performance work should protect the experience rather than flatten it.
The goal is not maximum technical optimization.
The goal is sufficient performance for the intended experience to remain intact.

7. Accessibility Philosophy
Accessibility is part of the architecture, not a final correction.
The core meaning and essential content of NoelClark.com should remain available when a visitor cannot experience every visual, motion, auditory, or interactive layer.
No essential meaning should depend exclusively on one sensory or interaction mode.
Visitor preferences should be respected wherever the experience intersects with them.
Accessibility adaptations should preserve meaning even when presentation changes.
Creative ambition does not override access.

8. Failure & Recovery Philosophy
Failure should be contained.
One failed enhancement should not unnecessarily break the surrounding experience.
Essential content should remain reachable whenever practical.
The project should fail toward clarity and simplicity rather than confusion.
Recovery should not require the visitor to understand what went wrong internally.
Temporary technical failure must not corrupt persistent visitor state.
A visitor should always have a reasonable path forward.

9. Engineering Discipline
Complexity must be earned.
Prefer the simplest architecture that can faithfully support the approved experience.
Preserve working systems unless change has a clear purpose.
Do not rewrite stable work merely because another approach is newer or more fashionable.
Prototype discoveries that earn their place should be preserved when moving into production.
Changes should be deliberate, testable, and limited to their intended scope.
Refactoring must preserve approved behavior unless behavioral change has been explicitly approved.
Technical convenience must not silently become creative authority.

10. AI Engineering Principles
AI is an engineering assistant, not a project authority. AI must respect the approved hierarchy of project documents.
AI-generated work must follow the approved project documents.
AI must not invent unresolved creative, architectural, or product decisions in order to complete an implementation.
When required information is missing, ambiguity should be surfaced rather than silently resolved.
Existing approved work should be examined before replacement work is generated.
AI should prefer modifying the smallest necessary surface over broad unsolicited rewrites.
AI-generated changes require the same review, testing, and project discipline as human-generated changes.
AI must not treat plausible output as verified output.
The repository and approved project documents remain authoritative.

11. Engineering Non-Negotiables
The experience governs the implementation.
Writing and essential content must remain accessible.
Visitor-controlled reading must remain visitor-controlled.
Approved architecture must not be redesigned for technical convenience.
Implementation must not expose mechanics that the approved experience requires to remain invisible.
Persistent state must be intentional and minimal.
Tracking must never become the purpose of the site.
Failure must preserve the clearest viable path forward.
Accessibility must be considered from the beginning.
Performance must protect the experience.
Complexity must be justified.
Approved work must not be discarded without reason.
Creative decisions must not be invented during engineering.
Every meaningful change must be testable.

12. Scope Reminder
This document defines principles.
It does not define the visitor journey.
It does not define individual features.
It does not define implementation tasks.
It does not choose technologies.
Those responsibilities belong to their respective project documents.
Engineering may change substantially over the life of NoelClark.com.
These principles should not.
If implementation and principle appear to conflict, examine the implementation first.
Changes to these principles require intentional review and explicit approval. Engineering should remain replaceable. These principles should not.

Engineering documentation should use the approved project vocabulary. New terminology should not be introduced when existing approved terminology already exists.
