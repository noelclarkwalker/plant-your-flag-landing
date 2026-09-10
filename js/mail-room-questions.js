/**
 * Mail Room optional questions — visitor-safe neutral IDs only.
 * Private backstage mapping: js/mail-room-questions-mapping.js (not loaded client-side).
 */
window.MAIL_ROOM_OPTIONAL_QUESTIONS = [
  {
    id: "mq01",
    prompt:
      "Q1. Stress happens to the best of us. How do you typically respond to stressors?",
    responses: [
      {
        id: "mq01_a",
        label: "I take on more, not less. I stay busy and end up carrying the whole thing myself.",
      },
      {
        id: "mq01_b",
        label: "I act on it immediately. I move before I've fully thought it through.",
      },
      {
        id: "mq01_c",
        label: "I pull back to understand what's happening before I do anything with it.",
      },
      {
        id: "mq01_d",
        label:
          "I absorb it fully. It stops feeling like something happening near me and starts feeling like something happening inside me.",
      },
    ],
  },
  {
    id: "mq02",
    prompt: "Q2. When tackling a task, what's your style?",
    responses: [
      {
        id: "mq02_a",
        label: "Steady: I keep a consistent pace throughout.",
      },
      {
        id: "mq02_b",
        label: "Surge: I go all in, then need to recover.",
      },
      {
        id: "mq02_c",
        label: "Build slowly: I need to work up to it before it clicks.",
      },
      {
        id: "mq02_d",
        label: "Rises and falls: It depends on what's happening around me.",
      },
    ],
  },
  {
    id: "mq03",
    prompt:
      "Q3. When something feels emotionally heavy or draining, how do you usually handle it?",
    responses: [
      {
        id: "mq03_a",
        label: "I keep functioning. I'll process it later, on my own time.",
      },
      {
        id: "mq03_b",
        label:
          "I need to move it out of me right away: talk, act, do something physical.",
      },
      {
        id: "mq03_c",
        label: "I need to sit with it privately and turn it over until it makes sense.",
      },
      {
        id: "mq03_d",
        label:
          "I absorb it fully, and it's hard to tell which parts are actually mine.",
      },
    ],
  },
  {
    id: "mq04",
    prompt: "Q4. Which kind of environment affects your mood the most?",
    responses: [
      {
        id: "mq04_a",
        label: "Disorganized ones: no structure to hold onto.",
      },
      {
        id: "mq04_b",
        label: "Ones where I feel micromanaged, or where too many decisions pile up at once.",
      },
      {
        id: "mq04_c",
        label: "Overstimulating ones: too much input, no room to think.",
      },
      {
        id: "mq04_d",
        label: "Emotionally tense ones: even the unspoken tension gets in.",
      },
    ],
  },
  {
    id: "mq05",
    prompt: "Q5. When you're in your zone, what does it look or feel like?",
    responses: [
      {
        id: "mq05_a",
        label: "Consistent: I can keep going long after I probably should stop.",
      },
      {
        id: "mq05_b",
        label: "Bursty: Everything happens at once, then I need to recharge.",
      },
      {
        id: "mq05_c",
        label: "Cyclical: It builds quietly underneath, then clicks all at once.",
      },
      {
        id: "mq05_d",
        label: "Variable: It depends heavily on who and what's around me.",
      },
    ],
  },
];
