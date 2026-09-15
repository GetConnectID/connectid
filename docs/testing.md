# ConnectID Testing Plan

## Registration

Test:

- valid registration
- duplicate username
- invalid username
- short password
- mismatched passwords
- empty fields

## Authentication

Test:

- valid login
- invalid password
- unknown username
- inactive account
- logout
- session protection

## Identity

Test:

- Citizen creation
- username uniqueness
- display name
- role
- reputation
- optional wallet

## Connections

Test:

- send connection
- duplicate connection
- self connection
- accept
- decline
- unauthorized actions

## Messaging

Test:

- messaging between accepted connections
- messaging without connection
- empty message
- oversized message
- unauthorized message access
- XSS payloads
- CSRF protection

## Communities

Test:

- create community
- join
- leave
- owner permissions
- duplicate membership
- unauthorized actions
- XSS payloads
- CSRF protection

## Reputation

Test:

- reputation event creation
- positive event
- negative event
- reputation history
- unauthorized reputation modification

## Security

Test:

- SQL injection
- XSS
- CSRF
- brute-force login attempts
- session handling
- authorization bypass
- direct backend access
- invalid IDs
- malformed requests

## Privacy

Test:

- private messages
- wallet privacy
- profile visibility
- unauthorized user access

## Production

Before launch:

- HTTPS
- production database
- secure configuration
- error handling
- backups
- database permissions
- server permissions
- removal of development credentials
