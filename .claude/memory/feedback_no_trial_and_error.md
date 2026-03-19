---
name: No trial and error
description: User demands thoroughly verified solutions — no guessing CLI flags, API signatures, or version compatibility
type: feedback
---

Never output code or commands without verifying they are correct for the target version. The user explicitly complained about trial-and-error iterations.

**Why:** Multiple rounds of broken DDEV setup commands (wrong CLI flags, wrong dependency versions, non-existent commands) wasted the user's time and trust.

**How to apply:** Before writing any CLI command, config, or dependency version — verify it against the actual API/docs for the target version. When unsure, research first (WebFetch docs, check source code) rather than guessing. One correct answer is worth more than three fast wrong ones.
