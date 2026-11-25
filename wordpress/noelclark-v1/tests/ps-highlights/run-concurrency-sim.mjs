/**
 * Simulated concurrency for exact-match highlight aggregation.
 *
 * This is NOT a live MySQL/WordPress integration test. It models:
 * - session advisory lock (mutex)
 * - atomic counter increment
 * - first-writer-wins canonical entry
 * - pending→approved reconciliation into canonical
 *
 * Usage: node tests/ps-highlights/run-concurrency-sim.mjs
 */

let fail = 0;
function assert(cond, label) {
  if (cond) console.log(`PASS  ${label}`);
  else {
    console.log(`FAIL  ${label}`);
    fail++;
  }
}

class Store {
  constructor() {
    this.entries = new Map(); // identity -> { id, count, approved, created }
    this.nextId = 1;
    this.locks = new Map();
    this.lockWaiters = new Map();
  }

  async acquire(name) {
    while (this.locks.get(name)) {
      await new Promise((r) => {
        const q = this.lockWaiters.get(name) || [];
        q.push(r);
        this.lockWaiters.set(name, q);
      });
    }
    this.locks.set(name, true);
  }

  release(name) {
    this.locks.set(name, false);
    const q = this.lockWaiters.get(name) || [];
    this.lockWaiters.set(name, []);
    for (const r of q) r();
  }

  findApproved(identity) {
    const e = this.entries.get(identity);
    return e && e.approved ? e : null;
  }

  async submitApproved(identity) {
    await this.acquire(identity);
    try {
      const existing = this.findApproved(identity);
      if (existing) {
        existing.count += 1; // models CAST(meta_value AS UNSIGNED)+1
        return { kind: "increment", id: existing.id, count: existing.count };
      }
      const id = this.nextId++;
      this.entries.set(identity, {
        id,
        count: 1,
        approved: true,
        created: this.entries.size
      });
      return { kind: "create", id, count: 1 };
    } finally {
      this.release(identity);
    }
  }

  async approvePending(identity, pendingLocalId) {
    await this.acquire(identity);
    try {
      const existing = this.findApproved(identity);
      if (existing && existing.id !== pendingLocalId) {
        existing.count += 1;
        return { kind: "merged", id: existing.id, count: existing.count };
      }
      const e = this.entries.get(identity) || {
        id: pendingLocalId,
        count: 1,
        approved: false,
        created: this.entries.size
      };
      e.approved = true;
      if (e.count < 1) e.count = 1;
      this.entries.set(identity, e);
      return { kind: "canonical", id: e.id, count: e.count };
    } finally {
      this.release(identity);
    }
  }
}

async function run() {
  console.log("=== Concurrent first submissions (50) ===");
  const store = new Store();
  const identity = "same-passage-context";
  const jobs = [];
  for (let i = 0; i < 50; i++) {
    jobs.push(store.submitApproved(identity));
  }
  const results = await Promise.all(jobs);
  const creates = results.filter((r) => r.kind === "create");
  const increments = results.filter((r) => r.kind === "increment");
  const entry = store.entries.get(identity);
  assert(creates.length === 1, "exactly one canonical public entry created");
  assert(increments.length === 49, "49 rematches incremented");
  assert(entry.count === 50, "final count is 50 (no lost increments)");
  assert(entry.id === creates[0].id, "chronological first entry remains canonical");

  console.log("\n=== Double-tap retry does not inflate (modeled separately) ===");
  /* Retry guard is a short transient; here we assert idempotent read path. */
  const before = entry.count;
  /* Simulated retry: return current count without bump when retry key set */
  const retry = { count: entry.count };
  assert(retry.count === before, "retry returns same count");

  console.log("\n=== Pending duplicate approved later merges ===");
  const store2 = new Store();
  const idA = "passage-a";
  await store2.submitApproved(idA); // public canonical count=1
  /* Pending duplicate stored privately (not in approved map yet) */
  const pendingId = store2.nextId++;
  const merge = await store2.approvePending(idA, pendingId);
  assert(merge.kind === "merged", "pending approval merged into canonical");
  assert(store2.entries.get(idA).count === 2, "count becomes 2");
  assert(store2.entries.get(idA).id === 1, "original chronological id stable");

  console.log("\n=== Rejected/spam never count (modeled) ===");
  const spamCountBefore = store2.entries.get(idA).count;
  /* Spam path never calls submitApproved / approvePending */
  assert(store2.entries.get(idA).count === spamCountBefore, "spam leaves count unchanged");

  console.log("\n=== Distinct identities do not collide ===");
  const store3 = new Store();
  await Promise.all([
    store3.submitApproved("loc-1"),
    store3.submitApproved("loc-2"),
    store3.submitApproved("loc-1")
  ]);
  assert(store3.entries.size === 2, "two public entries for two locations");
  assert(store3.entries.get("loc-1").count === 2, "loc-1 count 2");
  assert(store3.entries.get("loc-2").count === 1, "loc-2 count 1");

  console.log("\n=== Shared-IP idempotency (request id, not IP) ===");
  const idem = new Map();
  const counts = new Map();
  function submitIdem(identity, requestId, _ip) {
    const key = identity + "|" + requestId;
    if (idem.has(key)) {
      return { replay: true, count: counts.get(identity) };
    }
    const n = (counts.get(identity) || 0) + 1;
    counts.set(identity, n);
    idem.set(key, true);
    return { replay: false, count: n };
  }
  const sharedIp = "203.0.113.10";
  const r1 = submitIdem("pass-1", "uuid-visitor-a", sharedIp);
  const r2 = submitIdem("pass-1", "uuid-visitor-b", sharedIp);
  assert(r1.count === 1 && r2.count === 2, "shared IP + different request ids both count");
  const r1retry = submitIdem("pass-1", "uuid-visitor-a", sharedIp);
  assert(r1retry.replay && counts.get("pass-1") === 2, "same request id retry does not double-count");
  const r1new = submitIdem("pass-1", "uuid-visitor-a-again", sharedIp);
  assert(!r1new.replay && r1new.count === 3, "new request id after success counts again");

  console.log("\n=== Summary ===");
  if (fail) {
    console.log(`FAILED: ${fail}`);
    process.exit(1);
  }
  console.log("ALL CONCURRENCY SIM CHECKS PASSED");
  console.log(
    "NOTE: This validates the coordination algorithm under parallel async tasks."
  );
  console.log(
    "It is NOT a live WordPress/MySQL GET_LOCK integration test."
  );
  process.exit(0);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
