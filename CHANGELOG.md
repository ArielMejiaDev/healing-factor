# Changelog

All notable changes to `healing-factor` will be documented in this file.

## 1.0.0 - 2026-03-29

### Healing Factor v1.0 🚀

AI-powered self-healing for Laravel. Catch an exception, diagnose the cause, write the fix — automatically.

##### Drivers

- CLI — runs through Claude Code or Opencode using your existing subscription
- API — connects to the Claude API directly, pay-per-call

##### How it works

An Exception Listener catches errors and dispatches healing jobs to Laravel's queue. No external monitoring needed.

Hook up Bugsnag or Laravel Nightwatch webhooks and Healing Factor heals those errors too.

Track everything from the built-in Issue Tracker Dashboard.

##### What it fixes

- Undefined variables and properties
- Type errors
- Bad method calls
- Query errors
- Authorization and routing bugs
- Logic errors, off-by-one mistakes, bad validation rules

##### What it can't fix

Errors that prevent Laravel from booting — parse errors and fatal errors that kill the process before the queue runs.

Everything else? Handled.
