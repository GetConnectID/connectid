# ConnectID Security

## Security principles

Security is a fundamental part of ConnectID.

The application must protect:

- accounts
- identities
- private messages
- connections
- communities
- reputation
- wallet information

## Authentication

ConnectID uses authenticated sessions.

Passwords are stored using secure password hashing.

Passwords are never stored in plain text.

Sessions use:

- HttpOnly cookies
- SameSite protection
- secure cookies when HTTPS is available
- session ID regeneration after successful login

## Authorization

The backend determines what a user is allowed to do.

Frontend values must never be trusted for:

- roles
- permissions
- reputation
- Citizen status
- ownership
- Creator status

## CSRF protection

State-changing requests should use CSRF protection.

This applies to actions such as:

- creating connections
- accepting connections
- declining connections
- sending messages
- creating communities
- joining communities
- leaving communities
- changing account settings

## Input validation

All user input must be validated on the server.

The application must protect against:

- SQL injection
- cross-site scripting
- malformed input
- unexpected values
- oversized requests

## Database security

Database queries should use prepared statements.

Database credentials must never be committed to the public repository.

Production configuration belongs outside the public source code.

## Login protection

Repeated failed login attempts should be rate limited.

The system should monitor login attempts by:

- account identifier
- IP address
- time

The goal is to reduce:

- brute-force attacks
- credential stuffing
- automated login attempts

## Privacy

ConnectID must not become a public reverse lookup system for wallet addresses.

A wallet is optional.

A wallet does not automatically define identity or reputation.

Private messages must only be accessible to authorized participants.

## Creator security

Creator status must be controlled by the backend and database.

Creator status must never be determined by:

- URL parameters
- hidden form fields
- JavaScript
- frontend HTML
- user-submitted role values

## Reputation security

Reputation changes must be recorded as reputation events.

Users must not be able to directly submit their own reputation score.

## Secrets

The following must never be committed to the public repository:

- database passwords
- API keys
- private keys
- seed phrases
- session secrets
- production credentials

## Future security improvements

Future versions should consider:

- stronger rate limiting
- account recovery
- email verification
- passkeys
- multi-factor authentication
- suspicious login detection
- abuse reporting
- moderation tools
- audit logs
- wallet signature verification
