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

3. PLANT YOUR FLAG
Purpose
Establish the first authoritative state of Feature 01.
Protect the approved opening as a state of complete stillness before the visitor initiates movement.
Nothing belonging to a later state should compete with or alter this opening.
This Engineering Plan follows the synchronized Roadmap sequence. It intentionally distinguishes the opening state, PLANT YOUR FLAG, from the later narrative state, Planting My Flag. These are separate approved roadmap states with different engineering responsibilities.

Entry Condition
A first-time visitor begins the complete Feature 01 Arrival experience.
PLANT YOUR FLAG becomes the authoritative Arrival state.

Exit Condition
The visitor initiates the approved first movement.
The opening has completed its role and Borrowed Land may become authoritative.

State Transition
PLANT YOUR FLAG → Borrowed Land
The transition is visitor-initiated.
PLANT YOUR FLAG must not progress automatically for a first-time visitor.
Only the approved visitor action may begin progression into Borrowed Land.

Phase Owner
The Arrival system owns the PLANT YOUR FLAG state and its transition boundary.
No later Feature 01 state may independently initiate progression from this state.

Supporting Files
Exact supporting files must be determined by inspecting the current repository before implementation begins.
Likely responsibility areas to identify during repository inspection include:
* the production homepage structure;
* the presentation governing the opening;
* Feature 01 state ownership;
* the boundary between the opening and Borrowed Land;
* returning-visitor behavior where it intersects with the opening.
No file ownership should be assigned from assumption.

Assets
The approved project documents do not currently establish any phase-specific assets beyond the approved opening text. Repository inspection must confirm whether this state depends on existing shared assets.
The required visible content is the approved text:
PLANT YOUR FLAG
No additional asset should be introduced without approval.

Existing Work to Preserve
Preserve existing production work that already satisfies the approved opening:
* black background;
* white typography;
* PLANT YOUR FLAG;
* no period after the phrase;
* no logo;
* no navigation for the complete first-time journey;
* no movement before visitor initiation;
* the approved existing typography and composition unless a documented conflict is found.
Existing working behavior outside this state's responsibility should remain untouched.

Existing Work to Remove or Replace
Remove or replace only existing behavior that conflicts with the approved opening.
Conflicting behavior includes:
* automatic progression for a first-time visitor;
* visible navigation before the visitor begins the journey;
* visible logo or additional introductory content;
* movement before visitor initiation;
* later Feature 01 content becoming perceptibly active during the opening;
* obsolete behavior that bypasses the synchronized Feature 01 sequence.
No existing work should be removed merely because another implementation would be possible.

Phase-Specific Browser Responsibilities
The initial presentation must be visually stable before the visitor can perceive it.
Later Feature 01 states may prepare internally only if that preparation remains imperceptible and does not alter the opening.
The browser must not create unintended movement that contradicts the approved stillness.
The visitor's first approved movement must transfer progression cleanly to Borrowed Land.

Phase-Specific Accessibility Requirements
The opening statement must remain available as meaningful text independent of its visual presentation.
The visitor must have an accessible means of initiating the same progression available through the approved first movement.
Accessibility support must preserve the opening's meaning and must not create a separate narrative path.

Phase-Specific Performance Requirements
The opening must not wait on assets belonging exclusively to later states before becoming available.
Preparation for subsequent states must not delay or destabilize PLANT YOUR FLAG.

Phase-Specific Failure Conditions
Failure of later-state assets or enhancements must not prevent the PLANT YOUR FLAG opening from becoming available.
Failure in preparation for Borrowed Land must not cause later Feature 01 content to appear prematurely or leave the opening in an incoherent state.

Phase-Specific Engineering Risks
* Automatic behavior accidentally advancing a first-time visitor.
* Later-state initialization becoming visible during the approved still opening.
* Global navigation appearing before its approved point.
* Existing site-wide behavior introducing unintended movement.
* Returning-visitor logic interfering with the complete first-time journey.
* Treating PLANT YOUR FLAG as a conventional homepage state rather than the beginning of Feature 01.
* Modifying approved visual work unnecessarily while integrating state ownership.

Open Questions
1. What exact visitor action constitutes the approved first movement from PLANT YOUR FLAG into Borrowed Land?The approved documents establish that the visitor initiates the first movement and earlier project language identifies scrolling, but the synchronized engineering documents have not yet established the exact accepted input behavior across interaction modes.
2. What existing repository files currently own PLANT YOUR FLAG and its transition into the next state?This is a repository fact and must be answered through repository inspection before implementation.
3. How does the separately approved Returning Visitor Experience intersect technically with PLANT YOUR FLAG?The project establishes that returning visitors may bypass the complete journey and that returning-visitor behavior must preserve the first-time experience, but the exact ownership boundary between Feature 01 Arrival and the later Returning Visitor Experience must be confirmed before their behavior is connected.

Phase Completion Checklist
* PLANT YOUR FLAG is the authoritative initial state for the complete first-time Arrival.
* Approved opening content is preserved.
* No period appears after PLANT YOUR FLAG.
* No logo appears.
* No navigation appears during the first-time opening.
* No unapproved movement occurs before visitor initiation.
* No automatic first-time progression occurs.
* Later-state preparation remains imperceptible.
* Later-state failure cannot prevent the opening from appearing.
* The opening statement remains meaningfully accessible.
* The approved first movement transfers control only to Borrowed Land.
* Existing approved work has been inspected and preserved wherever compatible.
* Obsolete work has been identified before removal.
* Repository ownership has been confirmed.
* Open Questions affecting implementation have been resolved or intentionally deferred.
* The state has been tested independently.
* Its exit transition has been tested.
* The complete journey through PLANT YOUR FLAG has been replayed and reviewed against the approved project documents.
