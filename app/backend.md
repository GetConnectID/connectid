# ConnectID Backend Architecture

## Purpose

The ConnectID backend is responsible for turning the frontend prototype into a real platform.

The backend will manage:

- user accounts
- ConnectID Identity
- Citizens
- usernames
- profiles
- roles
- reputation
- connections
- messages
- communities
- optional wallet connections
- security
- authentication

The frontend must never be trusted with security-sensitive decisions.

---

# Technology

Initial backend technology:

- PHP
- MySQL
- HTML
- CSS
- JavaScript

The initial deployment target is Hostinger Web Hosting.

The architecture should remain modular so the backend can later be moved or expanded if ConnectID grows.

---

# ConnectID Identity

Every registered account receives one unique internal Identity ID.

The Identity ID is separate from:

- display name
- @username
- wallet address
- reputation
- role

The Identity ID is the permanent internal reference for the account.

Example:

ConnectID Identity
|
├── Identity ID
├── @username
├── Display name
├── Avatar
├── Citizen
├── Role
├── Reputation
├── Connections
└── Wallet (optional)

---

# Citizen

Every successfully registered user becomes a Citizen.

A wallet is not required to become a Citizen.

Initial registration:

1. Create account
2. Choose @username
3. Choose display name
4. Choose avatar
5. Create ConnectID Identity
6. Become Citizen

---

# Roles

Role is separate from reputation.

Initial role:

Citizen

Special role:

Creator

The Creator role is unique.

There must only be one Creator account.

The Creator role must be controlled by the backend and database.

A normal user must never be able to make themselves Creator by changing frontend code.

Future roles may include:

- Moderator
- Community Leader
- Verified
- Citizen

Creator remains the highest role.

---

# Reputation

Reputation is earned through participation and contribution.

Reputation is separate from role.

A user cannot purchase a higher reputation.

The exact reputation system will be implemented later.

The backend must store reputation independently from the frontend.

---

# Connections

ConnectID uses mutual connections rather than followers.

Example:

Sean sends a connection request to John.

John accepts.

Result:

Sean ↔ John

Both users are connected.

Connections will later be stored in a dedicated database table.

---

# Messages

Messages belong to the communication layer.

Messages are private between participants unless a future community feature specifically provides another type of communication.

Initial message system:

- sender
- receiver
- message
- timestamp
- read status

The frontend must never directly write messages into the database without backend validation.

---

# Communities

Communities allow Citizens to interact around shared interests.

A community can contain:

- community identity
- name
- description
- creator
- members
- messages
- creation date

Community functionality will be expanded after the core account and connection system is working.

---

# Wallet

A crypto wallet is optional.

ConnectID does not require a wallet for:

- registration
- Citizen status
- connections
- messaging
- communities

A wallet can later be connected to provide an additional verification layer.

The wallet is not the ConnectID Identity itself.

The wallet must not automatically determine reputation.

ConnectID must not become a public reverse lookup system where a wallet address automatically exposes a user's ConnectID profile.

---

# Database

Initial database tables:

users

connections

messages

communities

community_members

Future tables may include:

reputation_events

wallet_verifications

reports

notifications

---

# Security

Security is a core requirement.

The backend must:

- validate all user input
- use prepared database statements
- hash passwords securely
- protect authentication sessions
- prevent unauthorized role changes
- prevent unauthorized access to private messages
- prevent users from modifying other users
- protect against SQL injection
- protect against cross-site scripting
- protect sensitive configuration
- never store passwords in plain text

Database passwords, API keys, private keys and other secrets must never be committed to the public GitHub repository.

---

# Registration

Registration will eventually perform these operations:

1. Validate display name
2. Validate @username
3. Check username uniqueness
4. Hash password
5. Create unique Identity ID
6. Create Citizen
7. Assign initial role
8. Set initial reputation
9. Store avatar
10. Create account
11. Start secure session

The Creator account will receive the Creator role through a protected backend process.

---

# Authentication

ConnectID will support secure account authentication.

Initial authentication:

- username or email
- password
- secure session

Future authentication options may include:

- passkeys
- multi-factor authentication
- wallet signatures
- stronger identity verification

Account recovery must not depend exclusively on a crypto wallet.

---

# Privacy

ConnectID allows virtual identities.

A legal name is not required to be publicly displayed.

The platform should provide enough persistent identity and accountability to build trust without requiring users to expose their legal identity publicly.

---

# Architecture Principle

ConnectID should be modular.

Identity should not depend on the wallet.

Citizen should not depend on crypto.

Reputation should not depend solely on money.

Connections should not depend on followers.

The frontend should not control security-sensitive data.

Each component should be replaceable or expandable without rebuilding the entire platform.

---

# Development Order

The backend will be developed in this order:

1. Database
2. Configuration
3. Database connection
4. Registration
5. Authentication
6. Identity
7. Citizen
8. Roles
9. Profile
10. Connections
11. Messages
12. Communities
13. Reputation
14. Optional wallet
15. Security hardening
16. Testing
17. Production deployment

---

# Production Principle

The GitHub repository contains the application source code.

Production secrets remain outside the public repository.

Hostinger will eventually host the production application and database.

The frontend and backend should communicate through controlled backend endpoints.

ConnectID must be designed so the system can grow from a small community into a large digital society without requiring a complete rebuild.
