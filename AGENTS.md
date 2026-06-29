# AGENTS.md

# AI Agent Skills & Development Guidelines

> This document defines the expected behavior of AI coding agents contributing to this project.

Supported Agents:

* OpenAI Codex
* ChatGPT Agent
* Cursor
* Claude Code
* Gemini CLI
* Cline
* Roo Code
* Continue
* Windsurf
* Any AI-assisted development tool

---

# Project Philosophy

This is an enterprise-grade application.

The project must remain:

* Clean
* Modular
* Scalable
* Permission-driven
* Organization-aware
* Team collaborative
* Maintainable
* Production ready

Every generated code must satisfy enterprise software standards.

---

# Tech Stack

Backend

* Laravel 12+
* PHP 8.3+

Frontend

* Vue 3
* Inertia.js
* TypeScript
* Tailwind CSS

Database

* MySQL
* SQL Server

Libraries

* Spatie Laravel Permission
* Ziggy
* Vite

---

# Development Principles

## Never Hardcode Anything

Avoid hardcoding:

* Roles
* Organizations
* Teams
* Routes
* Navigation
* Menu Items
* Dashboard Cards
* Sidebar
* Permissions
* Feature Flags

Everything must come from:

* Database
* Config
* Permission
* Policy
* Service

---

# Permission Driven Architecture

Never write

```php
$user->hasRole('Administrator');
```

Never write

```php
if(Auth::user()->role == 'Admin')
```

Instead

```php
$user->can('employee.view')
```

or

```php
Gate::allows('employee.view')
```

Permissions control access.

Roles are organizational only.

---

# Organization Driven Architecture

The application supports multiple organizations.

Example

University

```
USM Main

USM Kidapawan

USM PALMA

USM Mlang
```

Each organization owns

* Users
* Departments
* Permissions
* Employees
* Settings

Never assume a single organization exists.

Always scope queries.

---

# Use Services

Business logic never belongs inside

* Controller
* Vue Component
* Middleware

Instead

```
Controller

↓

Service

↓

Repository (optional)

↓

Model
```

---

# Thin Controllers

Controllers should only

* Validate request
* Call service
* Return response

Nothing more.

---

# Never Duplicate Logic

If similar code already exists

Reuse it.

Extract

* Services
* Traits
* Helpers
* Composables
* Components

---

# Vue Rules

Prefer

Composable

↓

Reusable Component

↓

Page

Never duplicate:

* Modal
* Table
* Form
* Button
* Badge
* Alert
* Dropdown

---

# Component Structure

Large pages should become

```
Pages/

Users/

Components/

Forms/

Dialogs/

Tables/

Cards/
```

Avoid 1000-line Vue files.

---

# TypeScript

Never use

```ts
any
```

Always create

* Interface
* Type
* Enum

Prefer strong typing.

---

# Form Handling

Use

* useForm()
* Validation
* Loading state
* Error state
* Success state

Always disable submit while saving.

---

# Routing

Never hardcode URLs.

Instead

```ts
route('users.index')
```

using Ziggy.

---

# Redirects

Prefer

```php
return to_route('users.index');
```

Instead of

```php
return redirect('/users');
```

---

# Validation

Always use

Form Request

Example

```
StoreUserRequest

UpdateUserRequest
```

Never validate large requests inside controllers.

---

# Authorization

Every action must be authorized.

Example

```php
$this->authorize('employee.create');
```

or

```php
Gate::authorize('employee.create');
```

---

# Policies

Prefer policies over manual permission checking.

---

# Database

Always use

Transactions

when creating

* User
* Employee
* Organization
* Payroll
* Multiple records

Example

```php
DB::transaction(function () {

});
```

---

# Query Optimization

Avoid

```php
foreach (...)
{
    User::find(...)
}
```

Instead

Use

* eager loading
* joins
* withCount
* aggregate queries

Avoid N+1.

---

# Migrations

Every migration must

* use foreign keys
* cascade appropriately
* include indexes

Never leave orphan records.

---

# Naming Convention

Controllers

```
UserController
```

Services

```
UserService
```

Requests

```
StoreUserRequest
```

Policies

```
UserPolicy
```

Events

```
UserCreated
```

Jobs

```
SyncEmployeesJob
```

Use descriptive names.

---

# UI Design

UI must be

* Simple
* Professional
* Enterprise
* Responsive
* Accessible

Avoid flashy designs.

Prefer

Whitespace

Consistency

Hierarchy

Readability

---

# Tables

Tables should include

* Search
* Filters
* Pagination
* Export
* Column visibility (optional)
* Empty state
* Loading state

---

# Forms

Every form should support

* Validation
* Reset
* Loading
* Success
* Cancel

---

# Notifications

Prefer

Toast notifications

instead of blocking alerts.

---

# Error Handling

Never expose

* SQL errors
* Stack traces
* Internal exceptions

Use

* Logging
* Friendly messages

---

## Production Redirect Rule

Never use:

```php
return redirect()->with();
```

Use named routes instead:

```php
return to_route('users.index')->with('success', 'User saved successfully.');
```

or

```php
return redirect()->route('users.index')->with('success', 'User saved successfully.');
```

This ensures redirects work reliably in production.

---

## Delete Confirmation Rule

Every delete action must have a confirmation dialog.

Never delete immediately after clicking a button.

Required flow:

1. User clicks Delete
2. Confirmation dialog appears
3. User confirms
4. Delete request is submitted
5. Success or error toast is shown

Use enterprise-style dialogs, not basic browser alerts.

---

## UI Font and Design Standard

Use **Inter** as the default font.

The UI must be:

* Enterprise-grade
* Compact
* Clean
* Professional
* Responsive
* Accessible
* Consistent

Avoid large spacing, oversized cards, and flashy colors.

Prefer compact layouts suitable for admin systems, HRIS, CEE systems, dashboards, and data-heavy modules.

---

## Spatie Teams Rule

Use **Spatie Laravel Permission Teams** for organization/team-scoped roles and permissions.

Enable teams in `config/permission.php`:

```php
'teams' => true,
```

Use the current organization/team context before checking roles or permissions.

Example:

```php
use Spatie\Permission\PermissionRegistrar;

app(PermissionRegistrar::class)->setPermissionsTeamId($currentTeamId);
```

When switching teams, unset cached user role and permission relations before checking permissions again.

```php
auth()->user()->unsetRelation('roles')->unsetRelation('permissions');
```

Access must be scoped by team/organization.

Never assume roles and permissions are global unless explicitly required.

Reference: Spatie Teams Permissions documentation.


# Logging

Log

* critical operations
* exceptions
* security events

Avoid logging sensitive information.

---

# Security

Never

* trust frontend validation
* expose IDs unnecessarily
* disable authorization

Always

* validate
* authorize
* sanitize

---

# Feature Development Workflow

When implementing a feature

1. Understand requirements
2. Check existing architecture
3. Reuse existing services
4. Design database changes
5. Build backend
6. Build frontend
7. Add authorization
8. Add validation
9. Test
10. Refactor

---

# Refactoring Rules

Always leave code cleaner than before.

Reduce

* duplication
* complexity
* nesting

Increase

* readability
* maintainability

---

# Performance

Prefer

* pagination
* lazy loading
* eager loading
* caching where appropriate

Avoid unnecessary database calls.

---

# Code Style

Follow

PSR-12

Laravel conventions

Vue Style Guide

TypeScript strict mode

---

# Comments

Avoid obvious comments.

Write self-documenting code.

Only comment

* complex business logic
* algorithms
* important decisions

---

# Testing

Whenever practical, generate

* Feature Tests
* Unit Tests
* Policy Tests

New features should be testable.

---

# Documentation

Whenever introducing

* new module
* service
* architecture

Update

* README
* AGENTS.md
* relevant documentation

---

# Git

Use meaningful commits.

Good

```
Add organization-scoped employee management
```

Bad

```
fix
```

---

# Before Generating Code

Always ask yourself

* Is this reusable?
* Is this scalable?
* Is it permission driven?
* Is it organization aware?
* Is it maintainable?
* Is it enterprise ready?
* Is there an existing component I can reuse?

If the answer is "No"

Redesign before coding.

---

# AI Agent Behavior

The AI agent should act like a Senior Software Architect.

It should

* Prefer maintainability over shortcuts.
* Avoid hardcoded implementations.
* Reuse existing code before creating new code.
* Follow Laravel and Vue best practices.
* Preserve backward compatibility unless explicitly instructed otherwise.
* Produce production-ready code, not prototypes.
* Explain architectural trade-offs when introducing significant changes.
* Minimize technical debt with every contribution.

---

# Final Principle

Every line of code should improve the project.

Do not simply make it work.

Make it scalable.

Make it secure.

Make it maintainable.

Make it beautiful.

Make it production ready.
