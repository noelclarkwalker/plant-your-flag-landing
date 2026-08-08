2. Global Engineering Architecture
Feature 01 is one continuous Arrival system composed of distinct approved states.
Engineering boundaries exist to make the system understandable, testable, and maintainable.
They must not become perceptible seams in the visitor experience.
The approved project documents define what the experience means and the order in which it occurs.
This plan defines the responsibilities required to preserve that experience.

2.1 Overall Engineering Architecture
Feature 01 must operate as one coordinated system rather than a collection of independent effects.
The architecture must support:
* deterministic progression through the approved Feature 01 sequence;
* visitor-controlled progression where the approved experience gives control to the visitor;
* automatic progression only where explicitly approved;
* persistent identity for elements that continue across multiple states;
* clear separation between remembered NoelClark.com and present-day NoelClark.com;
* stable ownership of writing, environment, memory, navigation, and transitions;
* graceful reduction when nonessential enhancement fails;
* independent development review of each approved state;
* reliable replay of individual states and transitions without changing production behavior.
No individual state may redefine the Feature 01 sequence.
No technical subsystem may become an independent narrative authority.
The approved sequence is defined by ROADMAP.md.
The approved visitor experience is defined by FEATURE_01_TREATMENT.md.

2.2 State Model
Feature 01 has one authoritative Arrival state at any given point in the approved sequence.
State represents narrative position and system responsibility.
It must not be inferred independently by multiple parts of the system when those interpretations could disagree.
A state change may alter:
* which system owns the visitor's current experience;
* which interactions are available;
* which environmental conditions are active;
* which elements hold attention;
* which transition may occur next.
A state change does not automatically require a new environment, new layout, or new visible destination.
Some approved states exist within the same continuous environment.
Memory Field and Journal are the permanent example:
Memory Field owns the environment.
Journal owns the voice within it.
Journal does not replace Memory Field.
Wayback Memory, Typewriter, Memory Cascade, Expanding Room, Manifesto, and Planting My Flag continue within the remembered experience until Portal provides passage out of it.
Present-day NoelClark.com does not become the authoritative environment before Emergence.
Wander ends guided Arrival and returns control to normal site exploration.
State required only for development or debugging must remain separate from real visitor state.

2.3 Phase Transition Model
Every transition must have:
* one approved source state;
* one approved destination state;
* a defined entry condition;
* a defined completion condition;
* one authoritative owner;
* protection against duplicate or conflicting progression.
Transitions must follow the synchronized ROADMAP.md order.
A later state must not become authoritative before the preceding state has satisfied its approved exit condition.
Where both visitor action and automatic progression are approved, the first valid trigger to begin the transition assumes ownership. Competing triggers must not produce duplicate progression.
Where an element retains identity across states, the transition must preserve that identity rather than silently substitute an unrelated equivalent.
A transition may coordinate multiple systems.
It must still have one authoritative owner responsible for determining when the transition has completed.
Technical tuning may change without changing the approved source state, destination state, narrative order, or meaning.

2.4 Asset Ownership
Every Feature 01 asset must have a defined narrative owner.
An asset may be used across more than one state without acquiring multiple competing owners.
Shared assets retain one authoritative source and identity.
Memory assets belong to the remembered environment.
Present-day assets belong to the modern home unless an approved project document explicitly establishes otherwise.
Assets with specific narrative meaning must not be treated as interchangeable decoration.
The first Wayback memory remains the approved asset:
assets/images/wayback-01.jpg
with approved alternative text:
“An early NoelClark.com homepage screen from 2012 declaring purpose to honor late father's life and help others.”
Asset readiness must support the approved experience without allowing loading behavior to become an unintended narrative event.
A complete Feature 01 asset inventory must be established from the repository and approved project documents before asset-dependent work begins.
Unknown or unresolved asset requirements must remain open questions.
They must not be filled with plausible substitutes.

2.5 Module Ownership
Every major Feature 01 responsibility must have one authoritative owner.
Required ownership domains include:
* Arrival state;
* state transitions;
* social environment;
* persistent circular element;
* Memory Field;
* Journal;
* memory objects;
* environmental expansion;
* Portal;
* present-day home;
* navigation;
* returning-visitor state;
* development diagnostics.
Ownership defines responsibility, not necessarily physical file boundaries.
One owner may coordinate supporting systems.
Supporting systems must not independently redefine shared state or narrative progression.
Cross-state elements must retain stable ownership throughout their approved lifespan.
Ownership boundaries should prevent unrelated behavior from becoming coupled merely because two states happen to interact.
Exact module boundaries must be determined from the current repository and approved during engineering.
They must not be invented in advance where existing architecture has not yet been inspected.

2.6 Development Workflow
Feature 01 will be engineered incrementally in synchronized ROADMAP.md order.
Before changing a state:
1. Identify the approved purpose of the state.
2. Inspect the current repository work associated with it.
3. Identify approved work that must be preserved.
4. Identify obsolete work that must be removed or replaced.
5. Resolve or record open questions.
6. Make only the changes required for that state and its necessary boundaries.
7. Test the state independently.
8. Test its entry transition.
9. Test its exit transition.
10. Replay the complete journey through the completed state.
11. Review the result against the approved project documents.
* Complete the project's test, screenshot, commit, and push workflow only after the engineering section has been reviewed and approved.
A later state must not be used to conceal unresolved behavior in an earlier state.
Approved prototype work must be inspected before replacement work is considered.
Working production behavior outside the approved scope of a state should remain untouched unless a dependency requires change.
If a dependency requires broader change, that dependency must be identified before the broader change is made.

2.7 Developer Debugging Tools
Feature 01 requires development diagnostics that make system state understandable without becoming part of the visitor experience.
Development diagnostics should make it possible to determine:
* current Arrival state;
* previous Arrival state;
* expected next state;
* active transition, if any;
* source of the active transition;
* transition completion status;
* readiness of required assets;
* presence of required state-owned elements;
* first-time or returning-visitor status;
* whether a recovery path has been invoked;
* whether development replay is active.
Diagnostics must report system truth rather than maintain a separate competing interpretation of Feature 01 state.
Development diagnostics must not alter normal state progression merely by being present.
They must not become part of ordinary visitor presentation or interaction.
The exact diagnostic interface remains an engineering decision to be made later.

2.8 Phase Replay Strategy
Every approved Feature 01 state must be independently reachable during development.
A developer must not need to replay the complete Arrival journey from the beginning after every change.
State replay must reconstruct enough valid prerequisite state for the selected state to behave as it would during normal Arrival.
Replay must not create a simplified or alternate version of the state being tested.
Incoming and outgoing transition boundaries must also be independently replayable.
The complete first-time Arrival must always remain replayable from its true beginning.
Returning-visitor behavior must be independently testable.
Development replay state must remain separate from actual visitor-history state.
Testing a later state must not permanently mark earlier states as completed for real visitors.
If a state cannot be replayed honestly without information from preceding states, that dependency must be preserved rather than bypassed.
Development replay exists solely to accelerate engineering. It must never alter or simplify the production visitor experience.

2.9 Ambiguity and Open Questions
Approved project documents are authoritative over engineering inference.
If any state requires information not resolved in the approved documents, list it as an Open Question for that state rather than inventing an answer.
This applies to unresolved:
* creative decisions;
* state behavior;
* transition conditions;
* ownership;
* assets;
* preservation requirements;
* accessibility behavior unique to the state;
* failure behavior;
* dependencies;
* repository responsibility.
An open question must remain visible until intentionally resolved.
Engineering may identify options or constraints when useful for review, but an option does not become a decision merely because it appears technically plausible.
Repository inspection may answer factual questions about existing work.
It may not resolve missing creative or architectural decisions on its own.
No unresolved question should be silently converted into implementation behavior for the sake of completeness.
